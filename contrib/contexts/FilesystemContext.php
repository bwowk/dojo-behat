<?php

namespace Ciandt;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Exception;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Finder\SplFileInfo;


class FilesystemContext implements Context  {
    
    private $fs;
    
    public function __construct()
    {
        $this->fs = new Filesystem();
    }
    
    
    /**
     * @Then there should be at least one ":file" file on ":path"
     */
    public function thereShouldBeAtLeast($file, $path)
    {
        $finder = new Finder();
        $matches = $finder->name($file)->in($path);
        if (count($matches) == 0) {
            throw new Exception("No files matching $file were found on $path.");
        }
    }

    /**
     * @Then there should be no ":file" file(s) on ":path"
     */
    public function thereShouldBeNo($file, $path)
    {
        $finder = new Finder();
        $matches = $finder->name($file)->in($path);
        if (count($matches) != 0) {
            throw new Exception("Found files matching $file on $path, but shouldn't.");
        }
    }

    /**
     * @Then there should be only( a) ":file" file(s) on ":path"
     */
    public function thereShouldBeOnly($file, $path)
    {
        $finder = new Finder();
        $expected = $finder->name($file)->in($path);
        if (count($expected) == 0) {
            throw new Exception("No files matching $file on $path");
        }
        $expectedFiles = "";
        foreach ($expected as $file){
            if (is_file($file)){
                $expectedFiles .= $file->getFilename() . "\n";
            }
        }

        $finder = new Finder();
        $actual = $finder->in($path);
        $actualFiles = "";
        foreach ($actual as $file){
            if (is_file($file)){
                $actualFiles .= $file->getFilename() . "\n";
            }
        }

        Assert::assertEquals($expectedFiles,$actualFiles);

    }


    /**
     * @Then the ":file" files on ":path" should be smaller than :size :unit
     */
    public function filesShouldBeSmallerThan($file, $path, $size, $unit) {
        $finder = new Finder();
        $matches = $finder->name($file)->in($path);

        if (count($matches) == 0) {
            throw new Exception("No files matching $file on $path");
        }

        switch ($unit) {
            case 'bytes':
            case 'byte':
                $divisor = 1;
                break;
            case 'Kilobytes':
            case 'Kilobyte':
                $divisor = 1000;
                break;
            case 'Megabytes':
            case 'Megabyte':
                $divisor =  1000000;
                break;
            case 'Gigabytes':
            case 'Gigabyte':
                $divisor =  1000000000;
                break;
            default:
                throw new \InvalidArgumentException(
                    "Expected one of the units: byte(s), Kilobyte(s), Megabyte(s) or Gigabyte(s)");
        }

        foreach ($matches as $file){
            $actual_size = $file->getSize()/$divisor;
            if ($actual_size > $size){
                $fileName = $file->getFilename();
                throw new Exception("The file $fileName has $actual_size $unit. Expected less than $size $unit");
            }

        }
    }

    /**
     * @Given there's a comma separated list of ":file" files from ":path" on ":placeholder"
     */
    public function filesListOnPlaceholder($file, $path, $placeholder) {
        $finder = new Finder();
        $matches = $finder->name($file)->in($path);

        if (count($matches) == 0) {
            throw new Exception("No files matching $file on $path");
        }

        $files = array();

        foreach ($matches as $file){
                $files[] = $file->getFilename();
        }
        $list = implode(',',$files);
        $this->placeholders->setPlaceholder($placeholder,$list);
    }

    /**
     * @Then there should be a ":filename" file on ":path" with ":expected_content"
     * @Then there should be a ":filename" file on ":path" with:
     * @Then the file ":filename" on ":path" should contain ":expected_content"
     * @Then the file ":filename" on ":path" should contain:
     * 
     * The expected_content string may contain the following placeholders:
     * %e: Represents a directory separator, for example / on Linux.
     * %s: One or more of anything (character or white space) except the end of line character.
     * %S: Zero or more of anything (character or white space) except the end of line character.
     * %a: One or more of anything (character or white space) including the end of line character.
     * %A: Zero or more of anything (character or white space) including the end of line character.
     * %w: Zero or more white space characters.
     * %i: A signed integer value, for example +3142, -3142.
     * %d: An unsigned integer value, for example 123456.
     * %x: One or more hexadecimal character. That is, characters in the range 0-9, a-f, A-F.
     * %f: A floating point number, for example: 3.142, -3.142, 3.142E-10, 3.142e+10.
     * %c: A single character of any sort.
     */
    public function thereShouldBeAFileWith($filename, $path, $expected_content)
    {
        $file = self::getFile($filename, $path);
        $actual_content = $file->getContents();
        Assert::assertStringMatchesFormat("%A$expected_content%A", $actual_content);
    }

    /**
     * Gets a file handler for a single file given by glob filename
     *
     * @param $filename
     * @param $path
     * @return SplFileInfo
     * @throws Exception
     */
    protected static function getFile($filename, $path){
        $finder = new Finder();
        $finder->name($filename)->in($path);
        $count = count($finder);
        if ( $count > 1) {
            throw new Exception("Multiple files matching $filename were found on $path");
        }
        if ($count == 0) {
            throw new Exception("No files matching $filename were found on $path");
        }
        $iterator = $finder->getIterator();
        $iterator->rewind();
        return $iterator->current();
    }

    /**
     * @Then the file ":filename" on ":path" should not contain ":string"
     * @Then the file ":filename" should not contain:
     *
     */
    public function fileShouldNotContain($filename, $path, $unexpected_content)
    {
        $file = self::getFile($filename, $path);
        $actual_content = $file->getContents();

        Assert::assertStringNotMatchesFormat($unexpected_content, $actual_content);

    }



    /**
     * @Given the folder ":path" exists
     * @Given the folder ":path" exists with :perm permissions
     */
    public static function folderExists($path, $perm = 777){
        $mode = intval($perm,8);
        if (!is_dir($path)){
            mkdir($path,$mode,true);
        } else {
            chmod($path, $mode);
        }

    }

    /**
     * @Then the md5 hash of the folder ":path" should match ":md5"
     */
    public function assertRecursiveMd5Matches($path, $md5)
    {
        Assert::assertEquals($md5, $this->recursiveMd5($path));
    }

    /**
     * @Given the folder :path is empty
     * @Given there's an empty folder on :path
     * @Given there's an empty folder on :path with :perm permissions
     */
    public static function emptyFolder($path, $perm = 777){
        if (is_dir($path)) {
            self::recursiveRmdir($path);
        }
        self::folderExists($path, $perm);
    }

    /**
     * @Then the md5 hash of the file ":path" should match ":md5"
     */
    public function assertMd5Matches($path, $md5)
    {
        if (!is_file($path)) {
            throw new Exception("$path is not a file or does not exist");
        }

        Assert::assertEquals($md5, md5_file($path));
    }

    protected function recursiveMd5($path) {

        if (!is_dir($path)) {
            throw new Exception("$path is not a folder or does not exist");
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,RecursiveDirectoryIterator::SKIP_DOTS));
        $hashes = "";

        foreach ($iterator as $filename)
        {
            // filter out directories
            if ($filename->isFile()){
                $hashes .= md5_file($filename->getPath());
            }
        }
        return md5($hashes);
    }

    /**
     * @Given there's a file named ":filename" on ":path" with:
     * @Given there's a file named ":filename" on ":path"
     * @Given there's a file named ":filename" on ":path" with ":permissions" permissions and content:
     * @Given there's a file named ":filename" on ":path" with ":permissions" permissions
     */
    public function createFileWith($filename, $path, $permissions = null, PyStringNode $content = null){
        self::folderExists($path);
        $full_path = $path . $filename;
        if ($content) {
            file_put_contents($full_path, $content);
        } else {
            touch($full_path);
        }
        if ($permissions) {
            chmod($full_path, intval($permissions,8));
        }
    }

    /**
     * @Given there's no file named ":filename" on ":path"
     */
    public function removeFile($filename, $path){
        if (file_exists($path.$filename)) {
            unlink($path.$filename);
        } 
    }

    private static function recursiveRmdir($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ? self::recursiveRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function recursiveCopy($src,$dst){
        if (!is_dir($src)) {
            throw new Exception("$src is not a folder or does not exist");
        }
        if (!is_dir($dst)) {
            mkdir($dst, 777, true);
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $item)
        {
            $srcPath = $item->getPath();
            $dstPath = str_replace($src,$dst,$srcPath);
            if ($item->isDir()){
                if (!is_dir($dstPath)) {
                    mkdir($dstPath);
                }
            }
            if (!$item->isDir() && $item->isFile()){
                copy($srcPath,$dstPath);
            }
        }
    }





}
