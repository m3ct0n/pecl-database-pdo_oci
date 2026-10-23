--TEST--
PDO_OCI: Re-executing DML and DDL statements
--EXTENSIONS--
pdo
pdo_oci
--SKIPIF--
<?php
require(getenv('PDO_TEST_DIR').'/pdo_test.inc');
PDOTest::skip();
?>
--FILE--
<?php

require_once(getenv('PDO_TEST_DIR').'/pdo_test.inc');

$dbh = PDOTest::factory();
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dbh->exec("begin
             execute immediate 'drop table test_reexecute_dml';
             exception when others then
               if sqlcode <> -942 then
                 raise;
               end if;
           end;");
$dbh->exec('CREATE TABLE test_reexecute_dml (id NUMBER NOT NULL PRIMARY KEY, name VARCHAR2(20))');

$insert = $dbh->prepare('INSERT INTO test_reexecute_dml (id, name) VALUES (:id, :name)');
foreach ([1 => 'a', 2 => 'b', 3 => 'c'] as $id => $name) {
    $insert->execute([':id' => $id, ':name' => $name]);
}

$update = $dbh->prepare('UPDATE test_reexecute_dml SET name = :name WHERE id = :id');
$update->execute([':id' => 1, ':name' => 'a1']);
$update->execute([':id' => 2, ':name' => 'b1']);
$update->execute([':id' => 2, ':name' => 'b2']);
var_dump($update->rowCount());

$alter = $dbh->prepare("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
$alter->execute();
$alter->execute();
$alter->execute();
echo "alter session: ok\n";

$select = $dbh->prepare('SELECT name FROM test_reexecute_dml WHERE id = :id');
foreach ([1, 2, 3] as $id) {
    $select->execute([':id' => $id]);
    echo $select->fetchColumn(), "\n";
}

?>
--CLEAN--
<?php
require_once(getenv('PDO_TEST_DIR').'/pdo_test.inc');
$dbh = PDOTest::factory();
$dbh->exec('DROP TABLE test_reexecute_dml');
?>
--EXPECT--
int(1)
alter session: ok
a1
b2
c
