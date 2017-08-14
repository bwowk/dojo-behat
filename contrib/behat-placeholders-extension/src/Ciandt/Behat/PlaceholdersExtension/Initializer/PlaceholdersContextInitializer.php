<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ciandt\Behat\PlaceholdersExtension\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Ciandt\Behat\PlaceholdersExtension\Config\PlaceholdersRepository;


class PlaceholdersContextInitializer implements ContextInitializer
{
    private $repository;

    /**
     * Initializes initializer.
     *
     * @param Mink  $mink
     * @param array $parameters
     */
    public function __construct(PlaceholdersRepository $repository)
    {
        $this->repository = $repository;

    }

    /**
     * Injects a $placeholders variable on provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        $context->placeholders = $this->repository;
    }
}
