<?php
declare(strict_types=1);

namespace LoyaltyCorp\EasyRepository\Tests\Implementation\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use LoyaltyCorp\EasyRepository\Tests\AbstractTestCase;
use Mockery\MockInterface;

final class AbstractDoctrineOrmRepositoryTest extends AbstractTestCase
{
    /**
     * Repository should return exactly what the Doctrine repository returns.
     *
     * @return void
     */
    public function testAllReturnsExpectedArray(): void
    {
        $expected = [new \stdClass(), new \stdClass()];

        /** @var \Doctrine\Common\Persistence\ManagerRegistry $registry */
        $registry = $this->mockRegistry(null, function (MockInterface $repo) use ($expected): void {
            $repo->shouldReceive('findAll')->once()->withNoArgs()->andReturn($expected);
        });

        self::assertEquals($expected, (new DoctrineOrmRepositoryStub($registry))->all());
    }

    /**
     * Repository should call the entity repository to create the query builder with expected parameters.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testCreateQueryBuilderReturnsOrmQueryBuilder(): void
    {
        /** @var \Doctrine\Common\Persistence\ManagerRegistry $registry */
        $registry = $this->mockRegistry(null, function (MockInterface $repo): void {
            $queryBuilder = $this->mock(QueryBuilder::class);

            $repo->shouldReceive('createQueryBuilder')->once()->withArgs(function (string $alias, $indexBy): bool {
                self::assertEquals('alias', $alias);
                self::assertNull($indexBy);

                return true;
            })->andReturn($queryBuilder);
        });

        $createQueryBuilder = $this->getMethodAsPublic(DoctrineOrmRepositoryStub::class, 'createQueryBuilder');
        $createQueryBuilder->invoke(new DoctrineOrmRepositoryStub($registry), 'alias');
    }

    /**
     * Repository should call the Doctrine object manager to delete given objects.
     *
     * @return void
     */
    public function testDeleteCallsRemoveOnObjectManager(): void
    {
        $tests = [new \stdClass(), [new \stdClass(), new \stdClass()]];

        foreach ($tests as $test) {
            /** @var \Doctrine\Common\Persistence\ManagerRegistry $registry */
            $registry = $this->mockRegistry($this->getManagerExpectations('remove', $test));

            (new DoctrineOrmRepositoryStub($registry))->delete($test);
        }
    }

    /**
     * Repository should return exactly what the Doctrine repository returns.
     *
     * @return void
     */
    public function testFindReturnsExpectedValues(): void
    {
        $expected = ['found' => new \stdClass(), null];

        foreach ($expected as $identifier => $object) {
            /** @var \Doctrine\Common\Persistence\ManagerRegistry $registry */
            $registry = $this->mockRegistry(null, function (MockInterface $repo) use ($identifier, $object): void {
                $repo->shouldReceive('find')->once()->with($identifier)->andReturn($object);
            });

            self::assertEquals($object, (new DoctrineOrmRepositoryStub($registry))->find($identifier));
        }
    }

    /**
     * Repository should call the Doctrine object manager to save given objects.
     *
     * @return void
     */
    public function testSaveCallsRemoveOnObjectManager(): void
    {
        $tests = [new \stdClass(), [new \stdClass(), new \stdClass()]];

        foreach ($tests as $test) {
            /** @var \Doctrine\Common\Persistence\ManagerRegistry $registry */
            $registry = $this->mockRegistry($this->getManagerExpectations('persist', $test));

            (new DoctrineOrmRepositoryStub($registry))->save($test);
        }
    }

    /**
     * Get Doctrine object manager expectations for given method and objects.
     *
     * @param string $method
     * @param object|object[] $objects
     *
     * @return \Closure
     */
    private function getManagerExpectations(string $method, $objects): \Closure
    {
        return function (MockInterface $manager) use ($method, $objects): void {
            $times = \is_array($objects) ? \count($objects) : 1;

            $manager->shouldReceive($method)->times($times)->withArgs(function ($object): bool {
                self::assertInstanceOf(\stdClass::class, $object);

                return $object instanceof \stdClass;
            });
            $manager->shouldReceive('flush')->once()->withNoArgs();
        };
    }
}

\class_alias(
    AbstractDoctrineOrmRepositoryTest::class,
    'StepTheFkUp\EasyRepository\Tests\Implementation\Doctrine\ORM\AbstractDoctrineOrmRepositoryTest',
    false
);
