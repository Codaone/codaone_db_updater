<?php
/**
 * Created by PhpStorm.
 * User: juhni
 * Date: 8/26/14
 * Time: 2:11 PM
 */


class DashboardCodaoneDbUpdaterSettingsController extends Controller {

    private $schemaVersion = "0.3";
    /** @var $db ADOConnection */
    private $db;
	private $xmlFile = "";
	private $tables = false;
	private $selectedTables = array();

    public function __construct(){
        $this->db = Loader::db();
		$this->xmlFile = DIR_PACKAGES."/codaone_db_updater/db.xml";
    }

    public function on_before_render(){
        /** @var $html HtmlHelper */
        $html = Loader::helper('html');
        $this->addHeaderItem($html->css('select2.css', 'codaone_db_updater'));
        $this->addHeaderItem($html->javascript('select2.min.js', 'codaone_db_updater'));
		if(!is_writable($this->xmlFile)) {
			$error = "db.xml file is not writable! Please change it!";
			$this->set("error", $error);
		}
    }

    public function view() {
		$this->set("tables", $this->getTables());
		$this->set("selectedTables", $this->selectedTables);
    }

    public function update() {
        $db = Loader::db();
        $file = $this->xmlFile;
        $schema = new adoSchema($db);
        $schema->ExtractSchema();
    }

	public function getTables() {
		$db = Loader::db();
		$tables = $db->GetCol("SHOW TABLES");
		$fileCont = @file_get_contents($this->xmlFile);
		preg_match_all("/table name=\"(.*?)\"/", $fileCont, $match);
		if(count($match[1])) {
			foreach($match[1] as $tab) {
				$tables[] = trim($tab);
				$this->selectedTables[] = trim($tab);
			}
		}
		$retTables = array();
		foreach($tables as $tb) {
			$retTables[$tb] = $tb;
		}
		return $retTables;
	}

	public function save() {
		if(is_writable($this->xmlFile)) {
			$tables = $_REQUEST["tables"];//, array());
			if(count($tables)) {
				$xmlStr = '<?xml version="1.0"?>' . "\n"
					. '<schema version="' . $this->schemaVersion . '">' . "\n";
				foreach($tables as $t) {
					$xmlStr .= $this->extractSchema($t, false);
				}
				$xmlStr .= "</schema>\n";
				@file_put_contents($this->xmlFile, $xmlStr);
			}
		}
		$this->redirect("/dashboard/codaone_db_updater/settings/");
	}

    private function extractSchema($prefix,  $incSchemaTag = true) {
        $indent = "\t";
        $data = false;

        $old_mode = $this->db->SetFetchMode( ADODB_FETCH_NUM );
//		Loader::library("3rdparty/adodb/adodb-xmlschema03.inc");

        if($incSchemaTag) {
            $schema = '<?xml version="1.0"?>' . "\n"
                . '<schema version="' . $this->schemaVersion . '">' . "\n";
        } else {
            $schema = "";
        }
		if(!is_array($this->tables)) {
			$this->tables = $this->db->GetCol($this->db->metaTablesSQL);
		}
        if( count($this->tables)) {
            foreach( $this->tables as $table ) {
				if($prefix != $table) {
					continue;
				}
                $schema .= $indent . '<table name="' . htmlentities( $table ) . '">' . "\n";

                // grab details from database
                $rs = $this->db->Execute( 'SELECT * FROM ' . $table . ' WHERE -1' );
                $fields = $this->db->MetaColumns( $table );
                $indexes = $this->db->MetaIndexes( $table );

                if( is_array( $fields ) ) {
                    foreach( $fields as $details ) {
                        $extra = '';
                        $content = array();

                        if( isset($details->max_length) && $details->max_length > 0 ) {
                            $extra .= ' size="' . $details->max_length . '"';
                        }

                        if( isset($details->primary_key) && $details->primary_key ) {
                            $content[] = '<KEY/>';
                        } elseif( isset($details->not_null) && $details->not_null ) {
                            $content[] = '<NOTNULL/>';
                        }

                        if( isset($details->has_default) && $details->has_default ) {
                            if($details->default_value == "CURRENT_TIMESTAMP") {
                                $content[] = '<DEFTIMESTAMP/>';
                            } else {
                                $content[] = '<DEFAULT value="' . htmlentities( $details->default_value ) . '"/>';
                            }
                        }

                        if( isset($details->auto_increment) && $details->auto_increment ) {
                            $content[] = '<AUTOINCREMENT/>';
                        }

                        if( isset($details->unsigned) && $details->unsigned ) {
                            $content[] = '<UNSIGNED/>';
                        }

                        // this stops the creation of 'R' columns,
                        // AUTOINCREMENT is used to create auto columns
                        $details->primary_key = 0;
                        $type = $rs->MetaType( $details );
                        if($type == "N") {
                            $type = "decimal(10,5)";
                        }
						if($details->type == "longtext") {
							$type = "XL";
						}

                        $schema .= str_repeat( $indent, 2 ) . '<field name="' . htmlentities( $details->name ) . '" type="' . $type . '"' . $extra;

                        if( !empty( $content ) ) {
                            $schema .= ">\n" . str_repeat( $indent, 3 )
                                . implode( "\n" . str_repeat( $indent, 3 ), $content ) . "\n"
                                . str_repeat( $indent, 2 ) . '</field>' . "\n";
                        } else {
                            $schema .= "/>\n";
                        }
                    }
                }

                if( is_array( $indexes ) ) {
                    foreach( $indexes as $index => $details ) {
                        $schema .= str_repeat( $indent, 2 ) . '<index name="' . $index . '">' . "\n";

                        if( $details['unique'] ) {
                            $schema .= str_repeat( $indent, 3 ) . '<UNIQUE/>' . "\n";
                        }

                        foreach( $details['columns'] as $column ) {
                            $schema .= str_repeat( $indent, 3 ) . '<col>' . htmlentities( $column ) . '</col>' . "\n";
                        }

                        $schema .= str_repeat( $indent, 2 ) . '</index>' . "\n";
                    }
                }

                if( $data ) {
                    $rs = $this->db->Execute( 'SELECT * FROM ' . $table );

                    if( is_object( $rs ) && !$rs->EOF ) {
                        $schema .= str_repeat( $indent, 2 ) . "<data>\n";

                        while( $row = $rs->FetchRow() ) {
                            foreach( $row as $key => $val ) {
                                if ( $val != htmlentities( $val ) ) {
                                    $row[$key] = '<![CDATA[' . $val . ']]>';
                                }
                            }

                            $schema .= str_repeat( $indent, 3 ) . '<row><f>' . implode( '</f><f>', $row ) . "</f></row>\n";
                        }

                        $schema .= str_repeat( $indent, 2 ) . "</data>\n";
                    }
                }

                $schema .= $indent . "</table>\n";
            }
        }

        $this->db->SetFetchMode( $old_mode );

        if($incSchemaTag) {
            $schema .= '</schema>';
        }
        return $schema;
    }
}