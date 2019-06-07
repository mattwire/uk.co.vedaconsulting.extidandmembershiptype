<?php

require_once 'extidandmembershiptype.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function extidandmembershiptype_civicrm_config(&$config) {
  _extidandmembershiptype_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function extidandmembershiptype_civicrm_xmlMenu(&$files) {
  _extidandmembershiptype_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function extidandmembershiptype_civicrm_install() {
  _extidandmembershiptype_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function extidandmembershiptype_civicrm_uninstall() {
  _extidandmembershiptype_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function extidandmembershiptype_civicrm_enable() {
  _extidandmembershiptype_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function extidandmembershiptype_civicrm_disable() {
  _extidandmembershiptype_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function extidandmembershiptype_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  _extidandmembershiptype_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function extidandmembershiptype_civicrm_managed(&$entities) {
  _extidandmembershiptype_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function extidandmembershiptype_civicrm_caseTypes(&$caseTypes) {
  _extidandmembershiptype_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function extidandmembershiptype_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _extidandmembershiptype_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_pre
 */
function extidandmembershiptype_civicrm_pre ($op, $objectName, $id, &$params) {
}

/**
 * If a contribution is created/edited create/edit the slave contributions
 * @param $op
 * @param $objectName
 * @param $objectId
 * @param $objectRef
 *
 * @throws \CiviCRM_API3_Exception
 */
function extidandmembershiptype_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  switch ($objectName) {
    case 'Individual':
    case 'Organization':
      if ($op == 'edit' || $op == 'create') {
        $contactDetails = json_decode(json_encode($objectRef), TRUE);

        // For some reason 5.13 has started returning null values for some fields as string "null" instead of value null :-(
        if (!empty($contactDetails['external_identifier']) && $contactDetails['external_identifier'] !== "null") {
          return;
        }

        $callbackParams = [
          'entity' => $objectName,
          'op' => $op,
          'id' => $objectId,
          'details' => $contactDetails,
        ];
        if (CRM_Core_Transaction::isActive()) {
          CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, 'extidandmembershiptype_callback_civicrm_post', [$callbackParams]);
        }
        else {
          extidandmembershiptype_callback_civicrm_post($callbackParams);
        }
      }
      break;

  }
}

/**
 * Callback function for hook_civicrm_post
 *
 * @param $params
 *
 * @throws \CiviCRM_API3_Exception
 */
function extidandmembershiptype_callback_civicrm_post($callbackParams) {
  if (empty($callbackParams['id'])) {
    return;
  }

  $lock = Civi::lockManager()->acquire('data.core.extidandmembershiptypeAssignexternalid');
  if (!$lock->isAcquired()) {
    Civi::log()->warning('Could not acquire lock to assign external ID for cid=' . $callbackParams['id']);
    return;
  }
  $external_identifier = CRM_Extidandmembershiptype_Utils::getExternalIdentifier($callbackParams['id']);
  if (empty($external_identifier)) {
    //Don't know the latest external identifier and can't do max on as it is mix and match, that is why assigning random starting value 20000
    $external_identifier = CRM_Extidandmembershiptype_Utils::assignExternalIdentifier();
  }
  $contactParams = [
    'id' => $callbackParams['id'],
    'external_identifier' => $external_identifier,
  ];
  civicrm_api3('Contact', 'create', $contactParams);
  $lock->release();
}

