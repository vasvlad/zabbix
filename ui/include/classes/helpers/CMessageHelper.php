<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


/**
 * Helper to store success / error messages.
 */
class CMessageHelper {

	public const MESSAGE_TYPE_ERROR   = 'error';
	public const MESSAGE_TYPE_SUCCESS = 'success';

	/**
	 * @var string
	 */
	private static $type = self::MESSAGE_TYPE_SUCCESS;

	/**
	 * @var string
	 */
	private static $title;

	/**
	 * Messages array.
	 *
	 * @var array
	 */
	private static $messages = [];

	/**
	 * Get messages.
	 *
	 * @return array
	 */
	public static function getMessages(): array {
		return self::$messages;
	}

	/**
	 * Add message with type error.
	 *
	 * @param string $message
	 * @param string $source
	 */
	public static function addError(string $message, string $source = ''): void {
		self::$type = self::MESSAGE_TYPE_ERROR;
		self::$messages[] = [
			'type' => self::MESSAGE_TYPE_ERROR,
			'message' => $message,
			'source' => $source
		];
	}

	/**
	 * Add message with type info.
	 *
	 * @param string $message
	 */
	public static function addSuccess(string $message): void {
		self::$messages[] = [
			'type' => self::MESSAGE_TYPE_SUCCESS,
			'message' => $message
		];
	}

	/**
	 * Get messages title.
	 *
	 * @return string|null
	 */
	public static function getTitle(): ?string {
		return self::$title;
	}

	/**
	 * Set title for error messages.
	 *
	 * @param string $title
	 */
	public static function setErrorTitle(string $title): void {
		self::$type = self::MESSAGE_TYPE_ERROR;
		self::$title = $title;
	}

	/**
	 * Set title for info messages.
	 *
	 * @param $title
	 */
	public static function setSuccessTitle($title): void {
		self::$title = $title;
	}

	/**
	 * Get messages type.
	 *
	 * @return string
	 */
	public static function getType(): string {
		return self::$type;
	}

	/**
	 * Clear messages.
	 */
	public static function clear(): void {
		// self::$type = self::MESSAGE_TYPE_SUCCESS;
		// self::$title = null;
		self::$messages = [];
	}
}
