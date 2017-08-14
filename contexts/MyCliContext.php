<?php

namespace Ciandt;

use Ciandt\CliContext;
use Symfony\Component\Process\Process;


class MyCliContext extends CliContext
{
    const FIXTURES_FOLDER = './Konica/BackupRecovery/fixtures/';

    const MAIN_FOLDERS = array (
        'conf_folder' => '/etc/opt/konicaminolta/backup/',
        'log_folder' => '/var/log/konicaminolta/backup/',
        'lock_folder' => '/var/run/konicaminolta/backup/',
        'custom' => '/tmp/backuprestore/custom',
    );

    const MAINTENANCE_FILE = '/var/run/konicaminolta/maintanceMode.lock';

    const MONGO_CONTAINER = 'mongodb';
    const MONGO_SERVICE = 'mongod';
    const MONGO_FIXTURES = array(
        'drop_all' => self::FIXTURES_FOLDER . 'mongodb/dropAll.js',
        'tiny_fixture' => self::FIXTURES_FOLDER . 'mongodb/students.json',
    );
    const MONGO_ROOT = '/root/';
    const MONGO_DUMP_FOLDERS = array(
        'target_dump_folder' => '/var/opt/konicaminolta/backup/mongodb/dump/',
        'custom_dump_folder' => '/tmp/backuprestore/custom/mongodb/dump/',
    );
    const MONGO_FOLDERS = array(
        'tmp_folder' => '/tmp/konicaminolta/backup/mongodb/',
    ) + self::MAIN_FOLDERS + self::MONGO_DUMP_FOLDERS;


    const POSTGRES_CONTAINER = 'postgresql';
    const POSTGRES_SERVICE = 'postgresql';
    const POSTGRES_FOLDERS = array(
        'tmp_folder' => '/tmp/konicaminolta/backup/postgresql/',
        'target_dump_folder' => '/var/opt/konicaminolta/backup/postgresql/dump/',
        'custom_dump_folder' => '/tmp/backuprestore/custom/postgresql/dump/',
        'target_full_folder' => '/var/opt/konicaminolta/backup/postgresql/full/',
        'custom_full_folder' => '/tmp/backuprestore/custom/postgresql/full/',
        'target_inc_folder' => '/var/opt/konicaminolta/backup/postgresql/inc/',
        'custom_inc_folder' => '/tmp/backuprestore/custom/postgresql/inc/',
    ) + self::MAIN_FOLDERS;
    const POSTGRES_DUMP_FOLDERS = array(
        'target_dump_folder' => '/var/opt/konicaminolta/backup/postgresql/dump/',
        'custom_dump_folder' => '/tmp/backuprestore/custom/postgresql/dump/',
    );
    const POSTGRES_FULL_FOLDERS = array(
        'target_full_folder' => '/var/opt/konicaminolta/backup/postgresql/full/',
        'custom_full_folder' => '/tmp/backuprestore/custom/postgresql/full/',
    );
    const POSTGRES_INC_FOLDERS = array(
        'target_inc_folder' => '/var/opt/konicaminolta/backup/postgresql/inc/',
        'custom_inc_folder' => '/tmp/backuprestore/custom/postgresql/inc/',
    );
    const POSTGRES_FIXTURES = array(
        'drop_all' => self::FIXTURES_FOLDER . 'postgres/dropAll.sh',
        'tiny_fixture' => self::FIXTURES_FOLDER . 'postgres/tiny_fixture.sql',
    );

    const LDAP_CONTAINER = 'ldap';
    const LDAP_SERVICE = 'apacheds';
    const LDAP_FIXTURES = array(
        'tiny_fixture' => self::FIXTURES_FOLDER . 'ldap/tiny_fixture.tar.gz',
        'empty_fixture' => self::FIXTURES_FOLDER . 'ldap/empty.tar.gz',
    );
    const LDAP_ROOT = '/root/';
    const LDAP_DUMP_FOLDERS = array(
        'target_dump_folder' => '/var/opt/konicaminolta/backup/ldap/dump/',
        'custom_dump_folder' => '/tmp/backuprestore/custom/ldap/dump/',
    );
    const LDAP_FOLDERS = array(
        'tmp_folder' => '/tmp/konicaminolta/backup/mongodb/',
    ) + self::MAIN_FOLDERS + self::LDAP_DUMP_FOLDERS;


    /**
     * CLEANUP BEFORE ANY SETUP
     */

    /**
     * @BeforeScenario @mongodb,@postgresql,@ldap
     */
    public function cleanup(){
        exec('rm -r /etc/opt/konicaminolta/backup/');
        exec('rm -r /var/opt/konicaminolta/backup/');
        exec('rm -r /var/log/konicaminolta/backup/');
        exec('rm -r /var/run/konicaminolta/backup/');
        exec('rm -r /tmp/backuprestore/custom/');
        exec('mkdir -p /tmp/backuprestore/custom/');
        $maintenance = self::MAINTENANCE_FILE;
        exec("[ -f $maintenance ] && rm $maintenance");
    }

    private static function pushToContainer($localPath, $container, $remotePath){
        $push = new Process("lxc file push $localPath $container$remotePath");
        $push->mustRun();
    }

    private static function runOnContainer($container, $command){
        $runCommand = new Process("lxc exec $container -- $command");
        $runCommand->mustRun();
    }

    private static function serviceDown($container, $service){
        $containerDown = new Process("lxc exec $container -- systemctl stop $service");
        $containerDown->mustRun();
    }

    private static function serviceUp($container, $service){
        $containerUp = new Process("lxc exec $container -- systemctl start $service");
        $containerUp->mustRun();
    }

    private static function pushToMongo($file){
        self::pushToContainer( $file, self::MONGO_CONTAINER, self::MONGO_ROOT );
    }

    private static function runOnMongo($command){
        self::runOnContainer(self::MONGO_CONTAINER, $command);
    }

    private static function emptyFolder($folder){
        $files = glob("$folder*"); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }


    /**
     * @BeforeScenario @mongodb&&@tiny_fixture
     * @BeforeFeature @mongo_fixtures
     */
    public static function mongoSetupTinyFixtures(){
        $dropAll = 'dropAll.js';
        $fixture = 'students.json';
        self::runOnMongo("mongo $dropAll");
        self::runOnMongo("mongoimport --db test $fixture");
    }

    /**
     * @BeforeScenario @postgresql&&@tiny_fixture
     * @BeforeFeature @postgres_fixtures
     */
    public static function postgresqlSetupTinyFixtures(){
        $dropall = 'dropAll.sh';
        $fixture = 'tiny_fixture.sql';
        self::runOnContainer(self::POSTGRES_CONTAINER,"/root/$dropall");
        self::runOnContainer(self::POSTGRES_CONTAINER,"bash -c 'sudo -u postgres psql < /root/$fixture'");
    }

    private static function ldapSetupFixtures($fixture) {
        $partitions_folder = '/var/lib/apacheds-2.0.0-M21/default/partitions';
        $replace_partitions = "bash -c '"
            . "cd /root && "
            . "tar -xzvf $fixture && "
            . "rm -R $partitions_folder && "
            . "mv -T partitions $partitions_folder'";
        self::serviceDown(self::LDAP_CONTAINER,self::LDAP_SERVICE);
        self::runOnContainer(self::LDAP_CONTAINER,$replace_partitions);
        self::serviceUp(self::LDAP_CONTAINER,self::LDAP_SERVICE);
        self::waitLdapUp();
    }

    /**
     * @BeforeScenario @ldap&&@tiny_fixture
     * @BeforeFeature @ldap_fixtures
     */
    public static function ldapSetupTinyFixtures(){
        self::ldapSetupFixtures('tiny_fixture.tar.gz');
    }

    /**
     * @BeforeScenario @postgresql&&@empty
     */
    public static function postgresqlEmpty(){
        $dropall = 'dropAll.sh';
        self::runOnContainer(self::POSTGRES_CONTAINER,"/root/$dropall");
    }

    /**
     * @BeforeScenario @mongodb&&@empty
     */
    public function mongoEmpty(){
        $dropAll = 'dropAll.js';
        self::runOnMongo("mongo $dropAll");
    }

    /**
     * @BeforeScenario @ldap&&@empty
     */
    public function ldapEmpty(){
        self::ldapSetupFixtures('empty.tar.gz');
    }

    /**
     * @BeforeScenario @mongodb&&@fixture_medium
     */
    public function mongoSetupMediumFixtures(){
        $dropAll = 'dropAll.js';
        $fixture = 'fixture_medium.archive';
        self::runOnMongo("mongo $dropAll");
        self::runOnMongo("mongoimport --db test companies.json");
    }

    /**
     * @BeforeScenario @mongodb&&@bkp_dump_tgz
     */
    public function mongoSetupCompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'mongodb/dump/bkp_tgz/';
        foreach (self::MONGO_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @mongodb&&@bkp_dump_tar
     */
    public function mongoSetupUncompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'mongodb/dump/bkp_tar/';
        foreach (self::MONGO_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @mongodb&&@bkp_dump_fail
     */
    public function mongoSetupFailedBackup(){
        $fixture = self::FIXTURES_FOLDER.'mongodb/dump/bkp_fail/';
        foreach (self::MONGO_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_dump_tgz
     */
    public function postgresSetupCompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/dump/bkp_dump_tgz/';
        foreach (self::POSTGRES_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_dump_tar
     */
    public function postgresSetupUncompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/dump/bkp_dump_tar/';
        foreach (self::POSTGRES_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_dump_fail
     */
    public function postgresSetupFailedBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/dump/bkp_dump_fail/';
        foreach (self::POSTGRES_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }


    /**
     * @BeforeScenario @postgresql&&@bkp_full_tgz
     */
    public function postgresSetupCompressedFullBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/full/bkp_full_tgz/';
        foreach (self::POSTGRES_FULL_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_full_alt
     */
    public function postgresSetupAlternativeFullBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/full/bkp_full_alt/';
        foreach (self::POSTGRES_FULL_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_full_tar
     */
    public function postgresSetupUncompressedFullBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/full/bkp_full_tar/';
        foreach (self::POSTGRES_FULL_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_full_fail
     */
    public function postgresSetupFailedFullBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/full/bkp_full_fail/';
        foreach (self::POSTGRES_FULL_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_inc_tgz
     */
    public function postgresSetupCompressedIncrementalBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/inc/bkp_inc_tgz/';
        foreach (self::POSTGRES_INC_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_inc_tar
     */
    public function postgresSetupUncompressedIncrementalBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/inc/bkp_inc_tar/';
        foreach (self::POSTGRES_INC_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @postgresql&&@bkp_inc_fail
     */
    public function postgresSetupFailedIncrementalBackup(){
        $fixture = self::FIXTURES_FOLDER.'postgres/inc/bkp_inc_fail/';
        foreach (self::POSTGRES_INC_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @ldap&&@bkp_dump_tgz
     */
    public function ldapSetupCompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'ldap/dump/bkp_dump_tgz/';
        foreach (self::LDAP_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @ldap&&@bkp_dump_tar
     */
    public function ldapSetupUncompressedBackup(){
        $fixture = self::FIXTURES_FOLDER.'ldap/dump/bkp_dump_tar/';
        foreach (self::LDAP_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @ldap&&@bkp_dump_fail
     */
    public function ldapSetupFailedBackup(){
        $fixture = self::FIXTURES_FOLDER.'ldap/dump/bkp_dump_fail/';
        foreach (self::LDAP_DUMP_FOLDERS as $bkpFolder){
            exec("mkdir -p $bkpFolder");
            exec("chmod -R 777 $bkpFolder");
            exec("cp -aTr $fixture $bkpFolder");
        }
    }

    /**
     * @BeforeScenario @mongodb&&@down
     */
    public function mongoServiceDown(){
        self::serviceDown(self::MONGO_CONTAINER, self::MONGO_SERVICE);
        self::waitService(self::MONGO_CONTAINER,'mongo --eval "quit()"',"couldn't connect to server");
    }

    /**
     * @AfterScenario @mongodb&&@down
     */
    public static function mongoServiceUp(){
        self::serviceUp(self::MONGO_CONTAINER, self::MONGO_SERVICE);
        $mongoUp = false;
        while (!$mongoUp){
            $process = new Process('lxc exec mongodb -- mongo --eval "quit()"');
            $process->run();
            $mongoUp = $process->isSuccessful();
        }
    }

    /**
     * @BeforeScenario @postgresql&&@down
     */
    public function postgresqlServiceDown(){
        self::runOnContainer(self::POSTGRES_CONTAINER,'systemctl stop postgresql@9.5-main');
        self::waitService(self::POSTGRES_CONTAINER,'sudo -u postgres pg_isready',"no response");
    }

    /**
     * @When I wait for Postgresql to start
     */
    public static function waitPostgresUp(){
        $postgresUp = false;
        while (!$postgresUp){
            $process = new Process('lxc exec postgresql -- sudo -u postgres pg_isready');
            $process->run();
            $postgresUp = strpos($process->getOutput(),'accepting connections') !== false;
        }
    }

    /**
     * @AfterScenario @postgresql&&@down
     */
    public static function postgresqlServiceUp(){
        self::runOnContainer(self::POSTGRES_CONTAINER,'systemctl start postgresql@9.5-main');
        self::waitPostgresUp();
    }

    /**
     * @BeforeScenario @ldap&&@down
     */
    public function ldapServiceDown(){
        self::serviceDown(self::LDAP_CONTAINER,self::LDAP_SERVICE);
    }

    private static function waitLdapUp(){
        $whoami = 'ldapwhoami -h localhost -p 10389 -D "uid=admin,ou=system" -w secret';
        $ldapUp = false;
        while (!$ldapUp){
            $process = new Process("lxc exec ldap -- $whoami");
            $process->run();
            $ldapUp = strpos($process->getOutput(),'dn:uid=admin,ou=system') !== false;
        }
    }

    /**
     * @AfterScenario @ldap&&@down
     */
    public static function ldapServiceUp(){
        self::serviceUp(self::LDAP_CONTAINER,self::LDAP_SERVICE);
        self::waitLdapUp();
    }

    /**
     * @BeforeScenario @postgresql&&@archive_off
     */
    public function postgresqlArchiveOff(){
        $pg_conf = '/etc/postgresql/9.5/main/postgresql.conf';
        $sed = 'sed -i -e "s/^archive_mode = on/archive_mode = off/"';
        $pg_service = self::POSTGRES_SERVICE;
        self::runOnContainer(self::POSTGRES_CONTAINER, "$sed $pg_conf");
        self::runOnContainer(self::POSTGRES_CONTAINER, "systemctl restart $pg_service");
        $this->waitPostgresUp();
    }

    /**
     * @AfterScenario @postgresql&&@archive_off
     */
    public function postgresqlArchiveOn(){
        $pg_conf = '/etc/postgresql/9.5/main/postgresql.conf';
        $sed = 'sed -i -e "s/^archive_mode = off/archive_mode = on/"';
        $pg_service = self::POSTGRES_SERVICE;
        self::runOnContainer(self::POSTGRES_CONTAINER, "$sed $pg_conf");
        self::runOnContainer(self::POSTGRES_CONTAINER, "systemctl restart $pg_service");
        $this->waitPostgresUp();
    }

    /**
     * @BeforeScenario @maintenance
     */
    public function maintenanceOn(){
        touch(self::MAINTENANCE_FILE);
    }

    /**
     * @AfterScenario @maintenance
     */
    public function maintenanceOff(){
        unlink(self::MAINTENANCE_FILE);
    }

    public static function pushFixtures($container, $list, $dest){
        foreach ($list as $fixturePath){
            $fixture = basename($fixturePath);
            $exists = new Process("[ -f $dest/$fixture ] && exit 0 || exit 1");
            $exists->run();
            if (!$exists->isSuccessful()){
                $push = new Process("lxc file push $fixturePath $container$dest/ && lxc exec $container -- chmod 777 $dest/$fixture");
                $push->mustRun();
            }
        }
    }

    /**
     * @BeforeSuite
     */
    public static function envSetup(){
        exec('mkdir -p /tmp/backuprestore/custom');
        exec('mkdir -p /var/run/konicaminolta/');

        exec('lxc config device remove mongodb mongodbBackup');
        exec('lxc start mongodb');
        self::mongoServiceUp();
        self::pushFixtures(self::MONGO_CONTAINER,self::MONGO_FIXTURES,'/root');

        exec('lxc config device remove postgresql postgresqlBackup');
                exec('lxc start postgresql');
        self::postgresqlServiceUp();
        self::pushFixtures(self::POSTGRES_CONTAINER,self::POSTGRES_FIXTURES,'/root');

        exec('lxc config device remove ldap ldapBackup');
        exec('lxc start ldap');
        self::ldapServiceUp();
        self::pushFixtures(self::LDAP_CONTAINER,self::LDAP_FIXTURES,'/root');
    }


    /**
     * @AfterScenario @postgresql
     */
    public function postgresWaitUp(){
        self::waitPostgresUp();
    }

    private static function waitService($container, $test, $expected) {
        $passed = false;
        while (!$passed){
            $process = new Process("lxc exec $container -- $test ");
            $process->run();
            $output = $process->getOutput();
            $passed = strpos($output, $expected) !== false;
        }
    }
}
