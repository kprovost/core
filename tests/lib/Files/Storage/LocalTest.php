<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files\Storage;

/**
 * Class LocalTest
 *
 * @group DB
 *
 * @package Test\Files\Storage
 */
class LocalTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	protected function setUp() {
		parent::setUp();

		$this->tmpDir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->instance = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir]);
	}

	protected function tearDown() {
		\OC_Helper::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	public function testStableEtag() {
		if (\OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] On Windows platform we have no stable etag generation - yet');
		}

		$this->instance->file_put_contents('test.txt', 'foobar');
		$etag1 = $this->instance->getETag('test.txt');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertEquals($etag1, $etag2);
	}

	public function testEtagChange() {
		if (\OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] On Windows platform we have no stable etag generation - yet');
		}

		$this->instance->file_put_contents('test.txt', 'foo');
		$this->instance->touch('test.txt', time() - 2);
		$etag1 = $this->instance->getETag('test.txt');
		$this->instance->file_put_contents('test.txt', 'bar');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertNotEquals($etag1, $etag2);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidArgumentsEmptyArray() {
		new \OC\Files\Storage\Local([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidArgumentsNoArray() {
		new \OC\Files\Storage\Local(null);
	}

	/**
	 * @expectedException \OCP\Files\ForbiddenException
	 */
	public function testDisallowSymlinksOutsideDatadir() {
		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new \OC\Files\Storage\Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
	}

	public function testDisallowSymlinksInsideDatadir() {
		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub1/sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new \OC\Files\Storage\Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
	}
}

