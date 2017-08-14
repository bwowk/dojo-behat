<?php
namespace Ciandt\Behat\VisualRegressionExtension\Renderer;

use emuse\BehatHTMLFormatter\Formatter\BehatHTMLFormatter;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Ciandt\Behat\VisualRegressionExtension\Definitions\VisualCheckpoint;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Twig renderer for Behat report
 *
 * Class TwigRenderer
 * @package emuse\BehatHTMLFormatter\Renderer
 */
class TwigRenderer implements EventSubscriberInterface
{

    private $checkpoints = array();

    /**
     * Renders after an exercise.
     *
     * @param BehatHTMLFormatter $obj
     * @return string  : HTML generated
     */
    public function addCheckpoint(VisualCheckpoint $checkpoint)
    {
        $this->checkpoints[] = $checkpoint;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'tester.exercise_completed.after' => 'render',
        );
    }

    public function render()
    {
        $input = new ArgvInput($_SERVER['argv']);
        $profile = $input->getParameterOption(array('--profile', '-p')) ?: 'default';
        $baseFolder = "/code/visual_regression/$profile";

        $reportFolder = "$baseFolder/report";

        $reportFilename = "visual_regression.html";

        $this->copyAssets($reportFolder);

        $templatePath = dirname(__FILE__) . '/../../../../../templates';
        $loader = new Twig_Loader_Filesystem($templatePath);
        $twig = new Twig_Environment($loader, array());
        date_default_timezone_set('America/Sao_Paulo');
        $print = $twig->render('index.html.twig', array(
            'checkpoints' => $this->checkpoints,
            'datetime' => date("F j, Y, g:i a"),
            'profile' => $profile
            ));

        $report = fopen("$reportFolder/$reportFilename", "w");
        fwrite($report, $print);
        echo("report available on http://localhost:3000/browse/$profile/report/visual_regression.html\n");
    }

    public function rrmdir($src)
    {
        $dir = opendir($src);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    /**
     * Copies the assets folder to the report destination.
     *
     * @param string : the renderer
     */
    public function copyAssets($reportFolder)
    {
        // If the assets folder doesn' exist in the output path for this renderer, copy it
        $assets_source = dirname(__FILE__) . '/../../../../../assets';
        //first create the assets dir
        $destination = $reportFolder . DIRECTORY_SEPARATOR . 'assets';
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }
        $this->recurseCopy($assets_source, $destination);
    }

    /**
     * Recursivly copy a path.
     * @param $src
     * @param $dst
     */
    private function recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
