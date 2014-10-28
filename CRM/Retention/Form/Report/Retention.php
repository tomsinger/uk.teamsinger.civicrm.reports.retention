<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Retention_Form_Report_Retention extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_contribField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array('Membership', 'Contribution');
  protected $_customGroupGroupBy = FALSE;

  protected $_runQuery = FALSE;

  function __construct() {

    // Check if CiviCampaign is a) enabled and b) has active campaigns
  $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

    $this->_columns = array(
      'teamsinger_retention_report_type' =>
      array(
        'filters' =>
        array(
          'teamsinger_retention_report_type' =>
          array(
            'pseudofield' => 'true',
            'name' => 'teamsinger_retention_report_type',
            'title' => ts('Report Type'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              'not_yet_renewed' => 'Have Not Yet Renewed',
              'have_renewed' => 'Have Renewed'
            ),
          ),
        ),
      ),
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' =>
          array('title' => ts('First Name'),
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' =>
          array('title' => ts('Last Name'),
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' =>
          array(
            'title' => ts('Contact SubType'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' =>
        array(
          'membership_type_id' => array(
            'title' => 'Membership Type',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'membership_start_date' => array('title' => ts('Start Date'),
            'default' => TRUE,
          ),
          'membership_end_date' => array('title' => ts('End Date'),
            'default' => TRUE,
          ),
          'join_date' => array('title' => ts('Join Date'),
            'default' => TRUE,
          ),
          'source' => array('title' => 'Source'),
        ),
        'filters' => array(
/*
mark as pseudofield and then add to _whereClauses overriding buildQuery as Form/Member/ContributionDetail.php does?
*/

          'membership_end_date' =>
          array(
            'title' => 'Membership Renewal Due',
            'operatorType' => CRM_Report_Form::OP_DATE
          ),
          'tid' =>
          array(
            'name' => 'membership_type_id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_membership_status' =>
      array(
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'alias' => 'mem_status',
        'fields' =>
        array('name' => array('title' => ts('Status'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'sid' =>
          array(
            'name' => 'id',
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'grouping' => 'member-fields',
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array('phone' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'financial_type_id' => array('title' => ts('Financial Type')),
          'contribution_status_id' => array('title' => ts('Contribution Status')),
          'payment_instrument_id' => array('title' => ts('Payment Type')),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => NULL,
          'receipt_date' => NULL,
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'total_amount' => array('title' => ts('Payment Amount (most recent)'),
            'statistics' =>
            array('sum' => ts('Amount')),
          ),
        ),
        'filters' =>
        array(
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'grouping' => 'contri-fields',
      ),
    );
//    $this->_groupFilter = TRUE;
//    $this->_tagFilter = TRUE;

  // If we have active campaigns add those elements to both the fields and filters
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_membership']['fields']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'default' => 'false',
      );
      $this->_columns['civicrm_membership']['filters']['campaign_id'] = array('title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
      );
      $this->_columns['civicrm_membership']['order_bys']['campaign_id'] = array('title' => ts('Campaign'));

    }

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Retention Report'));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_contribution') {
              $this->_contribField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            if (array_key_exists('title', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
               INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_membership']}.contact_id AND {$this->_aliases['civicrm_membership']}.is_test = 0
               LEFT  JOIN civicrm_membership_status {$this->_aliases['civicrm_membership_status']}
                          ON {$this->_aliases['civicrm_membership_status']}.id =
                             {$this->_aliases['civicrm_membership']}.status_id ";


    //used when address field is selected
    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
    //used when phone field is selected
    if ($this->_phoneField) {
      $this->_from .= "
              LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_phone']}.contact_id AND
                           {$this->_aliases['civicrm_phone']}.is_primary = 1\n";
    }
    //used when contribution field is selected
    if ($this->_contribField) {
      $this->_from .= "
              LEFT JOIN (
                  SELECT cc.*, cmp.membership_id as membership_id
                  FROM civicrm_membership_payment cmp
                    JOIN civicrm_contribution cc
                      ON cc.id = cmp.contribution_id
                  ORDER BY cc.receive_date DESC
                  ) {$this->_aliases['civicrm_contribution']}
                ON {$this->_aliases['civicrm_membership']}.id =
                  {$this->_aliases['civicrm_contribution']}.membership_id\n";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_membership']}.membership_type_id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_membership']}.end_date";

    if ($this->_contribField) {
      $this->_orderBy .= ", {$this->_aliases['civicrm_contribution']}.receive_date DESC";
    }
  }

  function getFromTo($relative, $from, $to, $fromtime = NULL, $totime = NULL) {
    if (empty($totime)) {
      $totime = '235959';
    }
    //FIX ME not working for relative
    if ($relative) {
      list($term, $unit) = CRM_Utils_System::explode('.', $relative, 2);
      $dateRange = CRM_Utils_Date::relativeToAbsolute($term, $unit);
      $from = substr($dateRange['from'], 0, 8);
      //Take only Date Part, Sometime Time part is also present in 'to'
      $to = substr($dateRange['to'], 0, 8);
    }

    $from = CRM_Utils_Date::processDate($from, $fromtime);
    $to = CRM_Utils_Date::processDate($to, $totime);

    $report_type = CRM_Utils_Array::value("teamsinger_retention_report_type_value", $this->_params);

    if ($report_type == 'have_renewed' && !$this->_runQuery) {
      $this->_runQuery = TRUE;
      $original_from = new DateTime($from);
      $original_from->add(new DateInterval('P1Y'));
      $from = $original_from->format('YmdHis');
      $original_to = new DateTime($to);
      $original_to->add(new DateInterval('P1Y'));
      $to = $original_to->format('YmdHis');
    }

    return array($from, $to);
  }


  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    $contributionTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      if (array_key_exists('civicrm_membership_campaign_id', $row)) {
        if ($value = $row['civicrm_membership_campaign_id']) {
          $rows[$rowNum]['civicrm_membership_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

