<?php

class CRM_Extidandmembershiptype_Utils {

  const
    NEWHUMANIST_SETTING_GROUP = 'NewHumanist Preferences';

  /**
   * Get the existing external identifier for a contact ID
   *
   * @param $contactID
   *
   * @return bool|null
   */
  public static function getExternalIdentifier($contactID) {
    if (empty($contactID)) {
      return FALSE;
    }

    if (!empty(Civi::$statics[__CLASS__][$contactID]['external_identifier'])) {
      return Civi::$statics[__CLASS__][$contactID]['external_identifier'];
    }

    try {
      Civi::$statics[__CLASS__][$contactID]['external_identifier'] = civicrm_api3('Contact', 'getvalue', [
        'return' => "external_identifier",
        'id' => $contactID,
      ]);
      return Civi::$statics[__CLASS__][$contactID]['external_identifier'];
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Generate and return a new external identifier
   *
   * @return mixed
   */
  public static function assignExternalIdentifier() {
    // get most recent external id from custom setting.
    $maxMembershipNo = civicrm_api3('Setting', 'getvalue', [
      'return' => "name",
      'name' => "civi_max_membership_no",
    ]);

    $nextMembershipNumber = $maxMembershipNo;
    // Try and retrieve a contact ID for the current "max" external ID
    while (TRUE) {
      try {
        civicrm_api3('Contact', 'getvalue', ['external_identifier' => $nextMembershipNumber, 'return' => 'id']);
      }
      catch (Exception $e) {
        // Does not exist as API threw an error
        break;
      }
      // increment the next membership number
      $nextMembershipNumber = $nextMembershipNumber + 1;
    }
    // This ID has not yet been used, save it and return
    civicrm_api3('Setting', 'create', [
      'civi_max_membership_no' => $nextMembershipNumber,
    ]);
    return $nextMembershipNumber;
  }

}
