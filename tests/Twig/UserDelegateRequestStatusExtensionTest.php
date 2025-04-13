<?php

namespace App\Tests\Unit\Twig;

use App\Entity\UserDelegateRequest;
use App\Twig\UserDelegateRequestStatusExtension;
use PHPUnit\Framework\TestCase;

class UserDelegateRequestStatusExtensionTest extends TestCase
{
    private UserDelegateRequestStatusExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UserDelegateRequestStatusExtension();
    }

    public function testGetStatusWithAllValidStatuses(): void
    {
        $testCases = [
            [
                'status' => UserDelegateRequest::STATUS_NEW,
                'expected_label' => 'New',
                'expected_icon' => 'ti-help text-warning',
            ],
            [
                'status' => UserDelegateRequest::STATUS_CONFIRMED,
                'expected_label' => 'Confirmed',
                'expected_icon' => 'ti-circle-check text-success',
            ],
            [
                'status' => UserDelegateRequest::STATUS_REJECTED,
                'expected_label' => 'Rejected',
                'expected_icon' => 'ti-xbox-x text-error',
            ],
        ];

        foreach ($testCases as $case) {
            $result = $this->extension->getStatus($case['status']);

            $this->assertStringContainsString($case['expected_label'], $result);
            $this->assertStringContainsString($case['expected_icon'], $result);

            $this->assertStringStartsWith("<span class='ti", $result);
        }
    }

    public function testGetStatusWithInvalidStatus(): void
    {
        $invalidStatuses = [0, 999, -1];
        foreach ($invalidStatuses as $status) {
            $this->assertSame('None', $this->extension->getStatus($status));
        }
    }

    public function testGetStatusWithNonIntegerInput(): void
    {
        $this->expectException(\TypeError::class);
        $this->extension->getStatus('invalid');
    }
}
