<?php

namespace App\Shell\Task;

use Bake\Shell\Task\ModelTask;
use Cake\Core\Configure;

class ZeroModelTask extends ModelTask {
    const VERSION = 1.04;
    
    public function bake($name) {
        $path = $this->getPath();
        $namespace = Configure::read('App.namespace');
        
        $table = $this->getTable($name);
        $bakeTableObject = $this->getTableObject($name, $table);
        $tableObject = clone $bakeTableObject;
        $data = $this->getTableContext($bakeTableObject, $table, $name);
        
        $entityData = $data;
        $entityData['namespaceSuffix'] = '\Model\Entity\Bake';
        $entityData['extendClass'] = '\Model\Entity\AppEntity';
        
        $bakeTableObject->setAlias('Bake\\Bake'.$bakeTableObject->getAlias());
        $bakeTableName = $bakeTableObject->getAlias();
        $tableName = $tableObject->getAlias();
        $entityName = $this->_entityName($tableObject->getAlias());
        $bakeEntityName = $this->_entityName($bakeTableName);
        $data['entity'] = $entityName;
        $data['uses'] = null;
        $data['extendClass'] = '\Model\Table\AppTable';
        $data['namespaceSuffix'] = '\Model\Table\Bake';

        //bake 'BakeTable'
        $this->bakeTable($bakeTableObject, $data);
        //bake 'BakeEntity'
        $this->bakeEntity($bakeTableObject, $entityData);
        
        //bake 'Table'
        $tableFile = $path . 'Table' . DS . $tableName . 'Table.php';
        if(!file_exists($tableFile)) {
            $bakeTableClass = $namespace.'\\Model\\Table\\'.$bakeTableName.'Table';
            $this->bakeTable($tableObject, [
                'primaryKey' => false
            ]);
        }
        //bake 'Entity'
        $entityFile = $path . 'Entity' . DS . $entityName . '.php';
        if(!file_exists($entityFile)) {
            $this->bakeEntity($tableObject, [

            ]);
        }
        shell_exec('chmod -R 777 ' . $path . 'Entity');
        shell_exec('chmod -R 777 ' . $path . 'Table');
    }
    
    public function createFile($filename, $out) {
        $tableName = basename($filename, '.php');
        
        $newFilename = str_replace(['/', '\\'], [DS, DS], $filename);
        $newOut = str_replace('class Bake\\', 'class ', $out);
        $isBakeTemplate = $out !== $newOut;
        if($isBakeTemplate) {
            $newOut = preg_replace(['/namespace .*(\\Entity)/', '/namespace .*(\\Table)/'], '$0\\Bake', $newOut);
        } else {
            preg_match('/class (.*) /U', $newOut, $classMatches);
            $newOut = preg_replace(['/extends (Entity|Table)/'], 'extends Bake\\Bake'.$classMatches[1], $newOut);
            $newOut = preg_replace('|\* \@.*\*/|Us', '*/', $newOut);
            $newOut = preg_replace('|use.*;\n|', '', $newOut);
        }
;
        $createFile = parent::createFile($newFilename, $newOut);
        return $createFile;
    }
}
