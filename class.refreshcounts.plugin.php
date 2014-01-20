<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['RefreshCounts'] = array(
    'Name' => 'Refresh Counts',
    'Description' => 'Adds a button to the category management dashboard that will refresh the discussion counts for all the categories. Helpful in restoring order after a spam attack.',
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18.10'),
    'MobileFriendly' => TRUE,
    'HasLocale' => TRUE,
    'Author' => 'Zachary Doll',
    'AuthorEmail' => 'hgtonight@daklutz.com',
    'AuthorUrl' => 'http://www.daklutz.com',
    'License' => 'GPLv3'
);

class RefreshCounts extends Gdn_Plugin {

  public function SettingsController_Render_Before($Sender) {
    $RequestMethod = $Sender->RequestMethod;
    if($RequestMethod == 'managecategories' || $RequestMethod == 'tagging') {
      $Sender->AddJsFile($this->GetResource('js/refreshcounts.js', FALSE, FALSE));

      //check for any stashed messages from the pre
      $Message = Gdn::Session()->Stash('RefreshCountsMessage');
      if($Message) {
        //inform
        Gdn::Controller()->InformMessage($Message);
      }
    }
  }

  public function SettingsController_AfterRenderAsset_Handler($Sender) {
    $EventArguments = $Sender->EventArguments;
    $Method = $Sender->RequestMethod;
    if($EventArguments['AssetName'] == 'Content') {
      if($Method == 'managecategories') {
        echo Wrap(
                Wrap(T('RefreshCounts.CatHeading'), 'h3') .
                Wrap(
                        T('RefreshCounts.CatDescription') . ' ' .
                        Anchor(T('Refresh Counts'), '/categories/refreshcounts', array('class' => 'SmallButton', 'id' => 'RefreshCountsButton', 'title' => T('RefreshCounts.CatTooltip'))), 'div', array('class' => 'Info')), 'div', array('id' => 'RefreshCounts'));
      }
      else if($Method == 'tagging') {
        echo Wrap(
                Wrap(T('RefreshCounts.TagHeading'), 'h3') .
                Wrap(
                        T('RefreshCounts.TagDescription') . ' ' .
                        Anchor(T('Refresh Counts'), '/settings/refreshtagcounts', array('class' => 'SmallButton', 'id' => 'RefreshCountsButton', 'title' => T('RefreshCounts.TagTooltip'))), 'div', array('class' => 'Info')), 'div', array('id' => 'RefreshCounts'));
      }
    }
  }

  public function CategoriesController_RefreshCounts_Create($Sender) {
    $Sender->Permission('Vanilla.Categories.Manage');

    $DiscussionModel = new DiscussionModel();
    $CategoryModel = $Sender->CategoryModel;

    $Categories = $CategoryModel->GetAll();

    foreach($Categories as $Category) {
      $CategoryID = $Category->CategoryID;
      $DiscussionModel->UpdateDiscussionCount($CategoryID);
    }

    // stash the inform message for later
    Gdn::Session()->Stash('RefreshCountsMessage', T('RefreshCounts.Complete'));
    Redirect('/vanilla/settings/managecategories');
  }
  
  public function SettingsController_RefreshTagCounts_Create($Sender) {
    $Sender->Permission('Garden.Settings.Manage');

    $Px = $Sender->Database->DatabasePrefix;

    $SqlDriver = Gdn::SQL();
    // Delete all the orphaned tagdiscussion records
    $Sql = 'delete td.* ' .
            "from {$Px}TagDiscussion as td " .
            "left join {$Px}Discussion as d ON td.DiscussionID = d.DiscussionID " .
            'where d.DiscussionID is null';

    $SqlDriver->Reset();
    $SqlDriver->Query($Sql);
    
    // refresh the countdiscussions on all tags
    $Sql = "update {$Px}Tag as t " .
            'set CountDiscussions = ( ' .
            'select count(td.TagID) ' .
            "from {$Px}TagDiscussion as td " .
            'where td.TagID = t.TagID group by t.TagID)';
    
    $SqlDriver->Reset();
    $SqlDriver->Query($Sql);
    
    // stash the inform message for later
    Gdn::Session()->Stash('RefreshCountsMessage', T('RefreshCounts.TagComplete'));
    Redirect('/settings/tagging');
  }

}
