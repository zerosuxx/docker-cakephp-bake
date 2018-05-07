<?php

namespace App\Shell\Task;

use Bake\Shell\Task\ModelTask;
use Cake\Core\Configure;
use Cake\ORM\Table;

/**
 * @author Mohos Tamás <tomi@mohos.name>
 * @package App\Shell\Tas
 * @version 1.06
 */
class ZeroModelTask extends ModelTask {
    
    /**
     * @var string
     */
    private $modelNamespace = null;
    
    public function bake($name) {
        $path = $this->getPath();
        $namespace = Configure::read('App.namespace');
        $this->modelNamespace = $namespace . '\Model';
        //create AppTable & AppEntity
        $this->createAppTableAndEntity($path);
        
        $table = $this->getTable($name);
        $bakeTableObject = $this->getTableObject($name, $table);
        $tableObject = clone $bakeTableObject;
        $data = $this->getTableContext($bakeTableObject, $table, $name);

        $bakeTableObject->setAlias('Bake\\Bake'.$bakeTableObject->getAlias());
        $tableName = $tableObject->getAlias();
        $entityName = $this->_entityName($tableObject->getAlias());

        //bake 'BakeTable'
        $this->bakeTable($bakeTableObject, $data);
        //bake 'BakeEntity'
        $this->bakeEntity($bakeTableObject, $data);
        
        //bake 'Table'
        $tableFile = $path . 'Table' . DS . $tableName . 'Table.php';
        if(!file_exists($tableFile)) {
            $this->bakeTable($tableObject, [
                'primaryKey' => false
            ]);
        }
        //bake 'Entity'
        $entityFile = $path . 'Entity' . DS . $entityName . '.php';
        if(!file_exists($entityFile)) {
            $this->bakeEntity($tableObject);
        }
    }
    
    public function createFile($filename, $out) {
        $modelNamespace = $this->modelNamespace;
        $tableName = basename($filename, '.php');
        $isEntity = strpos($filename, 'Entity') !== false;
        $newFilename = str_replace(['/', '\\'], [DS, DS], $filename);
        $newOut = str_replace('class Bake\\', 'class ', $out);
        $isBakeTemplate = $out !== $newOut;
        if($isBakeTemplate) {
            $newOut = preg_replace(
                ['/use Cake\\\ORM\\\Table;/',
                '/use Cake\\\ORM\\\Entity;/'], 
                ['use ' . $modelNamespace . '\Table\AppTable as Table;',
                'use ' . $modelNamespace . '\Entity\AppEntity as Entity;'],
                $newOut
            );
            if(!$isEntity) {
                $newOut = preg_replace('/\\\Entity\\\Bake\\\Bake/', '\\Entity\\', $newOut);
            }
            $newOut = preg_replace(['/namespace .*(\\Entity)/', '/namespace .*(\\Table)/'], '$0\\Bake', $newOut);
        } else {
            preg_match('/class (.*) /U', $newOut, $classMatches);
            $newOut = preg_replace('/extends (Entity|Table)/', 'extends Bake\\Bake'.$classMatches[1], $newOut);
            $newOut = preg_replace('|\* \@.*\*/|Us', '*/', $newOut);
            $newOut = preg_replace('|use.*;\n|', '', $newOut);
            if($isEntity) {
                $newOut = preg_replace('|({)(.*)(})|Us', "$1\n\n$3", $newOut);
            }
        }

        $createFile = parent::createFile($newFilename, str_replace("\r\n", "\n", $newOut));
        return $createFile;
    }
    
    public function getAssociations(Table $table) {
        $associations = parent::getAssociations($table);
        $aliases = [];
        foreach($associations as $type => $assocs) {
            $i = 2;
            if(!isset($aliases[$type])) {
                $aliases[$type] = [];
            }
            foreach($assocs as $k => $assoc) {
                if(in_array($assoc['alias'], $aliases[$type])) {
                    $newAlias = $assoc['alias'] . '_' . $i;
                    if(!isset($assoc['className'])) {
                        $assoc['className'] = $assoc['alias'];
                    }
                    $assoc['alias'] = $newAlias;
                    $associations[$type][$k] = $assoc;
                    $i++;
                } else {
                    $aliases[$type][] = $assoc['alias'];
                }
            }
        }
        return $associations;
    }
    
    protected function createAppTableAndEntity($path) { 
        $appTableFile = $path . 'Table' . DS . 'AppTable.php';
        $appEntityFile = $path . 'Entity' . DS . 'AppEntity.php';
        if( !file_exists($appTableFile) ) {
            parent::createFile($appTableFile, 
            '<?php'."\n\n" . 

            'namespace ' . $this->modelNamespace . '\Table;' . "\n\n" .

            'use Cake\ORM\Table;' . "\n\n" . 

            'class AppTable extends Table {' . "\n\n" . 

            '}');
        }
        if( !file_exists($appEntityFile) ) {
            parent::createFile($appEntityFile, 
                '<?php'."\n\n" . 

                'namespace ' . $this->modelNamespace . '\Entity;' . "\n\n" .

                'use Cake\ORM\Entity;' . "\n\n" . 

                'class AppEntity extends Entity {' . "\n\n" . 

                '}');
        } 
    }
}
