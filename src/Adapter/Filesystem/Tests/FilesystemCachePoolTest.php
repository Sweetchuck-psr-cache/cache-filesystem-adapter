<?php

declare(strict_types = 1);

/**
 * @file
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\Adapter\Filesystem\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class FilesystemCachePoolTest extends TestCase
{
    use CreatePoolTrait;

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        static::tearDownAfterClassFilesystem();
        parent::tearDownAfterClass();
    }

    public function testInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $pool = $this->createCachePool();

        $pool->getItem('test%string')->get();
    }

    public function testCleanupOnExpire(): void
    {
        $pool = $this->createCachePool();

        $item = $pool->getItem('test_ttl_null');
        $item->set('data');
        $item->expiresAt(new \DateTime('now'));
        $pool->save($item);
        static::assertTrue($this->getFilesystem()->has('cache/test_ttl_null'));

        sleep(1);

        $item = $pool->getItem('test_ttl_null');
        static::assertFalse($item->isHit());
        static::assertFalse($this->getFilesystem()->has('cache/test_ttl_null'));
    }

    public function testChangeFolder(): void
    {
        $pool = $this->createCachePool();
        $pool->setFolder('foobar');

        $pool->save($pool->getItem('test_path'));
        static::assertTrue($this->getFilesystem()->has('foobar/test_path'));
    }

    public function testCorruptedCacheFileHandledNicely(): void
    {
        $pool = $this->createCachePool();

        $this->getFilesystem()->write('cache/corrupt', 'corrupt data');

        $item = $pool->getItem('corrupt');
        static::assertFalse($item->isHit());

        $this->getFilesystem()->delete('cache/corrupt');
    }
}
