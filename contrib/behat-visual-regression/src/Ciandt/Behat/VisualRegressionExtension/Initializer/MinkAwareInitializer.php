<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ciandt\Behat\VisualRegressionExtension\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;
use Ciandt\Behat\VisualRegressionExtension\Renderer\TwigRenderer;

/**
 * Mink aware contexts initializer.
 * Sets Mink instance and parameters to the MinkAware contexts.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class MinkAwareInitializer implements ContextInitializer
{
    private $baseline;
    private $visualRegression;
    private $renderer;
    
    public function __construct(TwigRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

        
    public function setBaseline($baseline)
    {
        $this->baseline = $baseline;
    }

    public function setVisualRegression($visualRegression)
    {
        $this->visualRegression = $visualRegression;
    }

    
    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof MinkAwareContext) {
            return;
        }
        
        $context->visualRegression = $this->visualRegression;
        $context->baseline = $this->baseline;
        $context->renderer = $this->renderer;
    }
}
