/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "zbxpoller.h"

#include "zbxjson.h"
#include "zbxcacheconfig.h"
#include "zbxsysinfo.h"
#include "zbxcomms.h"
#include "zbxtypes.h"
#include <stddef.h>
#include "zbxagentget.h"

/******************************************************************************
 *                                                                            *
 * Purpose: retrieve data from Zabbix agent                                   *
 *                                                                            *
 * Parameters: item             - [IN] item we are interested in              *
 *             config_source_ip - [IN]                                        *
 *             program_type     - [IN]                                        *
 *             result           - [OUT]                                       *
 *             version          - [IN/OUT] if 7.0.0 or higher, connect using, *
 *                                         JSON protocol, fallback and retry  *
 *                                         with plaintext protocol            *
 * Return value: SUCCEED - data successfully retrieved and stored in result   *
 *                         and result_str (as string)                         *
 *               NETWORK_ERROR - network related error occurred               *
 *               NOTSUPPORTED - item not supported by the agent               *
 *               AGENT_ERROR - uncritical error on agent side occurred        *
 *               FAIL - otherwise                                             *
 *                                                                            *
 * Comments: error will contain error message                                 *
 *                                                                            *
 ******************************************************************************/
int	zbx_agent_get_value(const zbx_dc_item_t *item, const char *config_source_ip, unsigned char program_type,
		AGENT_RESULT *result, int *version)
{
	zbx_socket_t	s;
	const char	*tls_arg1, *tls_arg2;
	int		ret = SUCCEED, retry = 0;
	ssize_t		received_len;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() host:'%s' addr:'%s' key:'%s' conn:'%s'", __func__, item->host.host,
			item->interface.addr, item->key, zbx_tcp_connection_type_name(item->host.tls_connect));

	switch (item->host.tls_connect)
	{
		case ZBX_TCP_SEC_UNENCRYPTED:
			tls_arg1 = NULL;
			tls_arg2 = NULL;
			break;
#if defined(HAVE_GNUTLS) || defined(HAVE_OPENSSL)
		case ZBX_TCP_SEC_TLS_CERT:
			tls_arg1 = item->host.tls_issuer;
			tls_arg2 = item->host.tls_subject;
			break;
		case ZBX_TCP_SEC_TLS_PSK:
			tls_arg1 = item->host.tls_psk_identity;
			tls_arg2 = item->host.tls_psk;
			ZBX_UNUSED(program_type);
			break;
#else
		case ZBX_TCP_SEC_TLS_CERT:
		case ZBX_TCP_SEC_TLS_PSK:
			SET_MSG_RESULT(result, zbx_dsprintf(NULL, "A TLS connection is configured to be used with agent"
					" but support for TLS was not compiled into %s.",
					get_program_type_string(program_type)));
			ret = CONFIG_ERROR;
			goto out;
#endif
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid TLS connection parameters."));
			ret = CONFIG_ERROR;
			goto out;
	}

	if (SUCCEED == zbx_tcp_connect(&s, config_source_ip, item->interface.addr, item->interface.port,
			item->timeout + 1, item->host.tls_connect, tls_arg1, tls_arg2))
	{
		struct zbx_json	j;
		char		*ptr;
		size_t		len;

		zbx_json_init(&j, ZBX_JSON_STAT_BUF_LEN);

		if (ZBX_COMPONENT_VERSION(7, 0, 0) <= *version)
		{
			zbx_agent_prepare_request(&j, item->key, item->timeout);
			ptr = j.buffer;
			len = j.buffer_size;
		}
		else
		{
			ptr = item->key;
			len = strlen(item->key);
		}

		zabbix_log(LOG_LEVEL_DEBUG, "Sending [%s]", ptr);

		if (SUCCEED != zbx_tcp_send_ext(&s, ptr, len, 0, ZBX_TCP_PROTOCOL, 0))
		{
			ret = NETWORK_ERROR;
		}
		else if (FAIL != (received_len = zbx_tcp_recv_ext(&s, 0, 0)))
		{
			ret = SUCCEED;
		}
		else if (SUCCEED != zbx_socket_check_deadline(&s))
		{
			ret = TIMEOUT_ERROR;
		}
		else
			ret = NETWORK_ERROR;

		zbx_json_free(&j);
	}
	else
	{
		ret = NETWORK_ERROR;
		goto out;
	}

	if (SUCCEED == ret)
	{
		if (FAIL == (ret = zbx_agent_handle_response(s.buffer, s.read_bytes, received_len,
				item->interface.addr, result, version)))
		{
			retry = 1;
		}
	}
	else
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Get value from agent failed: %s", zbx_socket_strerror()));

	zbx_tcp_close(&s);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	/* retry with other protocol */
	if (1 == retry)
		return zbx_agent_get_value(item, config_source_ip, program_type, result, version);

	return ret;
}
