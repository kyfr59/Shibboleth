<?php
/**
 * Shibboleth Login
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Shibboleth Login index controller class.
 *
 * @package Shibboleth Login
 */


class NakalaExport_IndexController extends Omeka_Controller_AbstractActionController
{    
    public function indexAction()
    {
        $this->_helper->db->setDefaultModelName('Item');

        // Respect only GET parameters when browsing.
        $this->getRequest()->setParamSources(array('_GET'));
        
        // Inflect the record type from the model name.
        $pluralName = $this->view->pluralize($this->_helper->db->getDefaultModelName());

        // Apply controller-provided default sort parameters
        if (!$this->_getParam('sort_field')) {
            $defaultSort = apply_filters("{$pluralName}_browse_default_sort",
                $this->_getBrowseDefaultSort(),
                array('params' => $this->getAllParams())
            );
            if (is_array($defaultSort) && isset($defaultSort[0])) {
                $this->setParam('sort_field', $defaultSort[0]);

                if (isset($defaultSort[1])) {
                    $this->setParam('sort_dir', $defaultSort[1]);
                }
            }
        }
        
        $params = $this->getAllParams();
        $recordsPerPage = $this->_getBrowseRecordsPerPage($pluralName);
        $currentPage = $this->getParam('page', 1);
        
        $records = $this->_helper->db
                        ->getTable('NakalaExport_Record')
                        ->getItemsToExport();

        if ($records)                
            $params['range'] = implode($records, ',');
        else
            $params['range'] = "no-results";
        
        // Get the records filtered to Omeka_Db_Table::applySearchFilters().
        $records = $this->_helper->db->findBy($params, $recordsPerPage, $currentPage);

        // Adding informations to recordset towards export to Nakala
        foreach($records as $key => $record)
        {
            // Adding log messages to results for items already tried to export
            $a = $this->_helper->db
                        ->getTable('NakalaExport_Record')           
                        ->hasAlwaysBeenExported($record->id, NakalaConsole_Helper::RESPONSE_ERROR);
            $records[$key]['status'] = $a['message'];
        }
        $totalRecords = $this->_helper->db->count($params);
        
        // Add pagination data to the registry. Used by pagination_links().
        if ($recordsPerPage) {
            Zend_Registry::set('pagination', array(
                'page' => $currentPage, 
                'per_page' => $recordsPerPage, 
                'total_results' => $totalRecords, 
            ));
        }
        
        $this->view->assign(array($pluralName => $records, 'total_results' => $totalRecords));
    }


}

