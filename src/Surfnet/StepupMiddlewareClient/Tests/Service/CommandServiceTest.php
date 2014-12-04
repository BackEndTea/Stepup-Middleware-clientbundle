<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddlewareClient\Tests\Service;

use Mockery as m;
use Surfnet\StepupMiddlewareClient\Service\CommandService;

class CommandServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider commandMetadata
     * @param bool $shouldHaveMeta
     * @param array $metadata
     */
    public function testItExecutesCommands($shouldHaveMeta, array $metadata)
    {
        $uuid = 'uu-id';
        $processedBy = 'server-1';
        $responseStub = m::mock('GuzzleHttp\Message\ResponseInterface')
            ->shouldReceive('json')->once()->andReturn(['command' => $uuid, 'processed_by' => $processedBy])
            ->shouldReceive('getStatusCode')->once()->andReturn('200')
            ->getMock();
        $guzzleClient = m::mock('GuzzleHttp\ClientInterface')
            ->shouldReceive('post')->once()->with(null, self::spy($options))->andReturn($responseStub)
            ->getMock();

        $username = 'user';
        $password = 'pass';
        $service = new CommandService($guzzleClient, $username, $password);

        $commandName = 'Root:Cause';
        $payload = [1];
        $command = $service->execute($commandName, $uuid, $payload, $metadata);

        $this->assertEquals($commandName, $options['json']['command']['name']);
        $this->assertEquals($uuid, $options['json']['command']['uuid']);
        $this->assertEquals($payload, $options['json']['command']['payload']);
        $this->assertEquals([$username, $password, 'basic'], $options['auth']);

        if ($shouldHaveMeta) {
            $this->assertEquals($metadata, $options['json']['meta']);
        } else {
            $this->assertArrayNotHasKey('meta', $options['json']);
        }

        $this->assertTrue($command->isSuccessful());
        $this->assertEmpty($command->getErrors());
        $this->assertEquals($command->getUuid(), $uuid);
        $this->assertEquals($command->getProcessedBy(), $processedBy);
    }

    public function commandMetadata()
    {
        return [
            'No metadata' => [false, []],
            'Has metadata' => [true, [2]],
        ];
    }

    public function testItHandlesErrorResponses()
    {
        $errors = ['Field X is fine', 'Field Y is durable', 'Field Z is zepto'];
        $responseStub = m::mock('GuzzleHttp\Message\ResponseInterface')
            ->shouldReceive('json')->once()->andReturn(['errors' => $errors])
            ->shouldReceive('getStatusCode')->once()->andReturn('400')
            ->getMock();
        $guzzleClient = m::mock('GuzzleHttp\ClientInterface')
            ->shouldReceive('post')->once()->with(null, self::spy($options))->andReturn($responseStub)
            ->getMock();

        $username = 'user';
        $password = 'pass';
        $service = new CommandService($guzzleClient, $username, $password);

        $commandName = 'Root:Cause';
        $uuid = 'abcdef';
        $payload = [1];
        $command = $service->execute($commandName, $uuid, $payload);

        $this->assertEquals($commandName, $options['json']['command']['name']);
        $this->assertEquals($uuid, $options['json']['command']['uuid']);
        $this->assertEquals($payload, $options['json']['command']['payload']);
        $this->assertEquals([$username, $password, 'basic'], $options['auth']);

        $this->assertFalse($command->isSuccessful(), "Command was executed successfully while it shouldn't have");
        $this->assertEquals($uuid, $command->getUuid());
        $this->assertNull($command->getProcessedBy());
        $this->assertCount(3, $command->getErrors());
    }

    public function testItThrowsWhenMalformedJsonIsReturned()
    {
        $this->setExpectedException('Surfnet\StepupMiddlewareClient\Exception\CommandExecutionFailedException');

        $responseStub = m::mock('GuzzleHttp\Message\ResponseInterface')
            ->shouldReceive('json')->once()->andThrow(new \RuntimeException)
            ->getMock();
        $guzzleClient = m::mock('GuzzleHttp\ClientInterface')
            ->shouldReceive('post')->once()->with(null, m::type('array'))->andReturn($responseStub)
            ->getMock();

        $service = new CommandService($guzzleClient, 'user', 'pass');

        $commandName = 'Root:Cause';
        $uuid = 'abcdef';
        $payload = [1];
        $service->execute($commandName, $uuid, $payload);
    }

    /**
     * @dataProvider invalidResponses
     * @param int $statusCode
     * @param array $response
     */
    public function testItThrowsWhenInvalidResponseIsReturned($statusCode, $response)
    {
        $this->setExpectedException('Surfnet\StepupMiddlewareClient\Exception\CommandExecutionFailedException');

        $responseStub = m::mock('GuzzleHttp\Message\ResponseInterface')
            ->shouldReceive('json')->once()->andReturn($response)
            ->shouldReceive('getStatusCode')->once()->andReturn((string) $statusCode)
            ->getMock();
        $guzzleClient = m::mock('GuzzleHttp\ClientInterface')
            ->shouldReceive('post')->once()->with(null, m::type('array'))->andReturn($responseStub)
            ->getMock();

        $service = new CommandService($guzzleClient, 'user', 'pass');

        $commandName = 'Root:Cause';
        $uuid = '1283-e93';
        $payload = [1];
        $service->execute($commandName, $uuid, $payload);
    }

    public function invalidResponses()
    {
        return [
            '200, missing command' => [200, ['processed_by' => 'server-3']],
            '200, missing processed_by' => [200, ['command' => 'uu-id-4']],
            '200, non-string command' => [200, ['command' => 3, 'processed_by' => 'server-3']],
            '200, non-string processed_by' => [200, ['command' => 'uid', 'processed_by' => 4]],
            '400, missing errors' => [400, ['command' => 'uu-id-4']],
            '500, errors a string' => [400, ['errors' => 'uu-id-4']],
            '500, command, processed_by' => [400, ['name' => 'uu-id', 'processed_by' => 'server-3']],
            '200, errors' => [200, ['errors' => ['hoi']]],
        ];
    }

    private static function spy(&$spiedValue)
    {
        return m::on(function ($value) use (&$spiedValue) {
            $spiedValue = $value;

            return true;
        });
    }
}