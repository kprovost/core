<?php
/**
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\DAV;

use OC\L10N\L10N;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\HookManager;
use OCP\IUserManager;
use Test\TestCase;

class HookManagerTest extends TestCase {

	/** @var L10N */
	private $l10n;

	public function setUp() {
		parent::setUp();

		$this->l10n = $this->getMockBuilder('OC\L10N\L10N')
			->disableOriginalConstructor()->getMock();
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));
	}

	public function test() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userManager */
		$userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$userManager->expects($this->once())->method('get')->willReturn($user);

		/** @var SyncService | \PHPUnit_Framework_MockObject_MockObject $syncService */
		$syncService = $this->getMockBuilder('OCA\DAV\CardDAV\SyncService')
			->disableOriginalConstructor()
			->getMock();

		/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject $cal */
		$cal = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUser')->willReturn([]);
		$cal->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal',
			[
				'{DAV:}displayname' => $this->l10n->t('Personal'),
				'{http://apple.com/ns/ical/}calendar-color' => '#1d2d44'
			]);

		/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject $card */
		$card = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUser')->willReturn([]);
		$card->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => $this->l10n->t('Contacts')]);

		$hm = new HookManager($userManager, $syncService, $cal, $card, $this->l10n);
		$hm->postLogin(['uid' => 'newUser']);
	}

	public function testWithExisting() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userManager */
		$userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$userManager->expects($this->once())->method('get')->willReturn($user);

		/** @var SyncService | \PHPUnit_Framework_MockObject_MockObject $syncService */
		$syncService = $this->getMockBuilder('OCA\DAV\CardDAV\SyncService')
			->disableOriginalConstructor()
			->getMock();

		/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject $cal */
		$cal = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUser')->willReturn([
			['uri' => 'my-events']
		]);
		$cal->expects($this->never())->method('createCalendar');

		/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject $card */
		$card = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUser')->willReturn([
			['uri' => 'my-contacts']
		]);
		$card->expects($this->never())->method('createAddressBook');

		$hm = new HookManager($userManager, $syncService, $cal, $card, $this->l10n);
		$hm->postLogin(['uid' => 'newUser']);
	}

	public function testWithBirthdayCalendar() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('newUser');

		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userManager */
		$userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$userManager->expects($this->once())->method('get')->willReturn($user);

		/** @var SyncService | \PHPUnit_Framework_MockObject_MockObject $syncService */
		$syncService = $this->getMockBuilder('OCA\DAV\CardDAV\SyncService')
			->disableOriginalConstructor()
			->getMock();

		/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject $cal */
		$cal = $this->getMockBuilder('OCA\DAV\CalDAV\CalDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$cal->expects($this->once())->method('getCalendarsForUser')->willReturn([
			['uri' => BirthdayService::BIRTHDAY_CALENDAR_URI]
		]);
		$cal->expects($this->once())->method('createCalendar')->with(
			'principals/users/newUser',
			'personal',
			[
				'{DAV:}displayname' => $this->l10n->t('Personal'),
				'{http://apple.com/ns/ical/}calendar-color' => '#1d2d44',
			]);

		/** @var CardDavBackend | \PHPUnit_Framework_MockObject_MockObject $card */
		$card = $this->getMockBuilder('OCA\DAV\CardDAV\CardDavBackend')
			->disableOriginalConstructor()
			->getMock();
		$card->expects($this->once())->method('getAddressBooksForUser')->willReturn([]);
		$card->expects($this->once())->method('createAddressBook')->with(
			'principals/users/newUser',
			'contacts', ['{DAV:}displayname' => $this->l10n->t('Contacts')]);

		$hm = new HookManager($userManager, $syncService, $cal, $card, $this->l10n);
		$hm->postLogin(['uid' => 'newUser']);
	}
}
