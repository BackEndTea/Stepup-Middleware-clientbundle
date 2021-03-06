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

namespace Surfnet\StepupMiddlewareClientBundle\Identity\Command;

use Surfnet\StepupMiddlewareClientBundle\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

class VetSecondFactorCommand extends AbstractCommand
{
    /**
     * @var string
     */
    public $authorityId;

    /**
     * @var string
     */
    public $identityId;

    /**
     * @var string
     */
    public $secondFactorId;

    /**
     * @var string
     */
    public $registrationCode;

    /**
     * @var string
     */
    public $secondFactorType;

    /**
     * @var string
     */
    public $secondFactorIdentifier;

    /**
     * @var string
     */
    public $documentNumber;

    /**
     * @var boolean
     */
    public $identityVerified;

    public function serialise()
    {
        return [
            'authority_id'             => $this->authorityId,
            'identity_id'              => $this->identityId,
            'second_factor_id'         => $this->secondFactorId,
            'registration_code'        => $this->registrationCode,
            'second_factor_type'       => $this->secondFactorType,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'document_number'          => $this->documentNumber,
            'identity_verified'        => $this->identityVerified,
        ];
    }
}
