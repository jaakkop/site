<?php
/**
 *  Campaigns -> Campaigns database model for Campaigns table.
 *
 *  Copyright (c) <2009>, Pekka Piispanen
 *
 *  This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 * 
 *  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied  
 *  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for  
 *  more details.
 * 
 *  You should have received a copy of the GNU General Public License along with this program; if not, write to the Free 
 *  Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * License text found in /license/
 */

/**
 *  Campaigns - class
 *
 *  @package    models
 *  @author     Pekka Piispanen, Mikko Aatola
 *  @license    GPL v2
 *  @version    1.0
 */ 
class Default_Model_Campaigns extends Zend_Db_Table_Abstract
{
    // Table name
    protected $_name = 'campaigns_cmp';

    // Table primary key
    protected $_primary = 'id_cmp';

    // Table dependet tables
    protected $_dependentTables = array('Default_Model_CampaignHasContent');

    /**
    *   campaignExists
    *
    *   Check if campaign exists in database.
    *
    *   @param string $campaign name of campaign to be checked
    *   @return boolean
    */
    public function campaignExists($id_cmp)
    {
        // Select campaign with given name
        $select = $this->select()->where('id_cmp = ?', $id_cmp);
        
        // Find all matching campaigns
        $result = $this->fetchAll($select)->toArray();
        
        return !empty($result);
    } // end of campaignExists

    /**
    *   getCampaign
    *
    *   Get campaign info and return an array containing campaign data
    *
    *   @param string $campaign name of campaign to be fetched
    *   @return array
    */
    public function getCampaign($campaign)
    {
        // Select campaign by name
        $select = $this->select()->where('name_cmp = ?', $campaign)
                                 ->limit(1);
                                 
        $row = $this->fetchAll($select)->current();
        
        return $row;
    } // end of getCampaign

    public function getCampaignById($campaign)
    {
        // Select campaign by id
        $select = $this->select()->where('id_cmp = ?', $campaign)
                                 ->limit(1);

        $row = $this->fetchAll($select)->current();

        return $row;
    }

    /**
     * getCampaignsByGroup
     *
     * Get campaigns by group id.
     *
     * @param $groupid id of the group
     */
    public function getCampaignsByGroup($groupid)
    {
        $select = $this->select()->where('id_grp_cmp = ?', $groupid);

        return $this->fetchAll($select)->toArray();
    }

    /**
    *   createCampaign
    *
    *   Adds a given campaign to database and returns the created row
    *
    *   @param string $name name of campaign that will be created
    *   @return array
    */
    public function createCampaign($name, $ingress, $desc, $start, $end, $group)
    {
        // Create new empty row
        $row = $this->createRow();
        
        // Set campaign data
        $row->name_cmp = $name;
        $row->ingress_cmp = $ingress;
        $row->description_cmp = $desc;

        // MM/DD/YYYY -> YYYY-MM-DD
        if (isset($start)) {
            $start = explode('/', $start);
            $start = implode('-', array($start[2], $start[0], $start[1]));
        } else {
            $start = date("Y-m-d", time());
        }
        $end = explode('/', $end);
        $end = implode('-', array($end[2], $end[0], $end[1]));


        $row->start_time_cmp = $start;
        $row->end_time_cmp = $end;
        $row->id_grp_cmp = $group;
        
        $row->created_cmp = new Zend_Db_Expr('NOW()');
        $row->modified_cmp = new Zend_Db_Expr('NOW()');
        
        // Save data to database
        $row->save();
        
        return $row;
    } // end of createCampaign

    public function editCampaign($id, $name, $ingress, $desc)
    {
		$data = array(
            'name_cmp' => $name,
            'ingress_cmp' => $ingress,
            'description_cmp' => $desc,
        );

		$where = $this->getAdapter()->quoteInto('id_cmp = ?', $id);
		$this->update($data, $where);
    }

    /**
    *   getAll
    *
    *   Gets all campaigns
    *
    *   @return array
    */
    public function getAll()
    {
        return $this->fetchAll();
    } // end of getAll

    /**
     * getRecent
     *
     * Gets the specified number of the most recently created campaigns.
     *
     * @param int $limit
     * @return array
     */
    public function getRecent($limit)
    {
        if (!isset($limit)) $limit = 10;

        $select = $this->select()
                ->order('id_cmp DESC')
                ->limit($limit);
        return $this->fetchAll($select)->toArray();
    }

    /**
     * getRecentFromOffset
     *
     * Gets a specified number of recent campaigns
     * starting from a specified offset.
     *
     * @param int $page
     * @param int $count
     */
    public function getRecentFromOffset($page, $count)
    {
        $select = $this->select()
                ->order('id_cmp DESC')
                ->limitPage($page, $count);
        return $this->fetchAll($select)->toArray();
    }
    
    /** 
    *   removeCampaign
    *   Removes the campaign from the database
    *   
    *   @param int id_cmp
    *   @author Pekka Piispanen, Mikko Aatola
    */
    public function removeCampaign($id_cmp = 0)
    {
        // Delete campaign-content links from cmp_has_cnt
        $cmpHasCntModel = new Default_Model_CampaignHasContent();
        $cmpHasCntModel->removeAllContentFromCampaign($id_cmp);

        // Delete campaign weblinks
        $cmpWeblinksModel = new Default_Model_CampaignWeblinks();
        $cmpWeblinksModel->removeCampaignWeblinks($id_cmp);

        // Delete campaign.
        $where = $this->getAdapter()->quoteInto('id_cmp = ?', $id_cmp);
        $this->delete($where);
    } // end of removeCampaign

    /**
     * Returns all contents in the specified campaign.
     *
     * @author Mikko Aatola
     * @param id_cmp id of the campaign
     * @return array of contents in the specified campaign
     */
    public function getAllContentsInCampaign($id_cmp)
    {
        $data = $this->_db->select()
            ->from(array('chc' => 'cmp_has_cnt'),
                   array('id_cnt'))
            ->join(array('cnt' => 'contents_cnt'),
                   'chc.id_cnt = cnt.id_cnt',
                   array('id_cty_cnt', 'title_cnt', 'lead_cnt'))
            ->join(array('chu' => 'cnt_has_usr'),
                    'chc.id_cnt = chu.id_cnt',
                    array('id_usr'))
            ->join(array('usr' => 'users_usr'),
                    'usr.id_usr = chu.id_usr',
                    array('login_name_usr'))
            ->join(array('cty' => 'content_types_cty'),
                    'cty.id_cty = cnt.id_cty_cnt')
            ->joinLeft('cnt_has_usr',
                    'cnt_has_usr.id_usr = chu.id_usr',
                    array('count' => 'count(*)'))
            ->group('cnt.id_cnt')
            ->where('id_cmp = ?', $id_cmp);
             
        
        $result = $this->_db->fetchAll($data);
        // this is a horrible way to do this
        if(is_array($result) && $result[0]['id_cnt'] != NULL) {
            $data = array();
            $contentHasTagModel = new Default_Model_ContentHasTag();
            $i = 0;
            foreach($result as $content) {
                $data[$i] = $content;
                $data[$i]['tags'] = $contentHasTagModel->getContentTags($content['id_cnt']);
                //Zend_Debug::dump($data[$i]['tags']);
               if ('finfo' == $content['key_cty']) {
                    $data[$i]['key_cty'] = 'visions';
                } elseif ('idea' == $content['key_cty']){
                    $data[$i]['key_cty'] = 'ideas';
                } elseif ('problem' == $content['key_cty']) {
                    $data[$i]['key_cty'] = 'challenge';
                }
                $i++;
            }
            //Zend_Debug::dump($data);
            return $data;
        }

        return $result;
    }

} // end of class