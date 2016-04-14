<?php
/**
 * @file
 * Class containing basic Tripolis Soap methods.
 *
 * This class should be extended to make API calls, because
 * Tripolis has a different WSDL for every group of calls,
 * grouped by functionality.
 *
 * Be aware that this module is also used as standalone software,
 * so it should not include or use Drupal functions!
 * Do anything Drupal related in a separate module.
 *
 * @see https://td42.tripolis.com/api2/docs/api
 */

/**
 * Class TripolisAPISoap.
 */
class TripolisAPISoap {
  protected $debug = FALSE;
  protected $soapHeader = 'http://services.tripolis.com/';
  protected $soapPrefix = 'https://td42.tripolis.com/api2/soap/';

  protected $client;
  protected $username;
  protected $password;
  protected $soapClient;
  protected $dbId = '';
  protected $workspaceId = '';

  protected $lastRequest;
  protected $lastResponse;

  /**
   * Constructor function.
   *
   * @param string $client
   *   Client name.
   * @param string $username
   *   Username to use for API.
   * @param string $password
   *   API password.
   */
  public function __construct($client, $username, $password) {
    $this->client = $client;
    $this->username = $username;
    $this->password = $password;
  }

  /**
   * Set debug mode on or off.
   *
   * @param bool $switch
   *   BOOL to set debug mode with.
   */
  public function setDebug($switch) {
    $this->debug = $switch;
  }

  /**
   * Setup the soap client.
   *
   * @param string $wsdl_service
   *   WSDL service name.
   *   For example 'ArticleService' will result in calls to WSDL
   *   https://td42.tripolis.com/api2/soap/ArticleService?wsdl .
   */
  public function setupSoap($wsdl_service) {

    $auth_values = array(
      'username' => $this->username,
      'password' => $this->password,
      'client' => $this->client,
    );
    $header = new SoapHeader($this->soapHeader, 'authInfo', $auth_values, FALSE);

    $soap_options = array(
      'trace'              => TRUE,
      'exceptions'         => TRUE,
      'connection_timeout' => 1,
      'features' => SOAP_USE_XSI_ARRAY_TYPE + SOAP_SINGLE_ELEMENT_ARRAYS,
    );
    $this->soapClient = new SoapClient($this->soapPrefix . $wsdl_service . '?wsdl', $soap_options);
    $this->debug($this->soapPrefix . $wsdl_service . '?wsdl', 'endpoint used');
    $this->soapClient->__setSoapHeaders(array($header));

  }

  /**
   * Centralized method for soap calling.
   *
   * @param string $method
   *   API method.
   * @param array $params
   *   Soap parameters, will form Soap body.
   * @param bool $get_errors
   *   TRUE: Don't throw exceptions, but return error object.
   *   FALSE: Throw exception and print on debug mode.
   *
   * @return mixed
   *   Response array payload, or SoapFault object.
   */
  public function call($method, array $params, $get_errors = FALSE) {
    $this->debug($params, 'request params');
    // Perform request and create associated array.
    try {
      if (!method_exists($this->soapClient, '__soapCall')) {
        return FALSE;
      }

      $result = json_decode(json_encode($this->soapClient->__soapCall($method, array($params))), TRUE);
      $this->debug($result, 'results from API');

      if (isset($result['response'])) {
        return $result['response'];
      }
      return $result;
    }
    catch (SoapFault $e) {
      if ($get_errors) {
        return $e;
      }
      else {
        $this->throwError($e);
        return $e;
      }
    }
  }

  /**
   * Page a soapcall, retrieve ALL items.
   *
   * Be aware that this methods converts ALL objects to arrays.
   *
   * @param string $method
   *   Soap method to call.
   * @param array $param
   *   Parameters of soap body.
   * @param int $pagesize
   *   Page size, items per page.
   *
   * @return array
   *   Merged array of all results.
   */
  public function pagedSoapCall($method, array $param, $pagesize = 400) {
    $merged_result = array();

    $call = TRUE;
    $pagenr = 1;
    reset($param);
    $first_key = key($param);
    $param[$first_key]['paging']['pageSize'] = $pagesize;

    while ($call) {
      $param[$first_key]['paging']['pageNr'] = $pagenr;
      $result = $this->call($method, $param);

      // Stop the pager if nothing is left, or just not paged at all.
      if (!$result || !is_array($result) || !isset($result['paging']['totalItems']) || $pagenr * $pagesize >= $result['paging']['totalItems']) {
        $call = FALSE;
      }

      // Merge.
      if ($result && is_array($result)) {
        $merged_result = array_merge_recursive($merged_result, $result);
      }

      $pagenr++;
    }

    return $merged_result;
  }

  /**
   * Get last request.
   *
   * @return string
   *   Soap request.
   */
  public function getLastRequest() {
    return $this->soapClient->__getLastRequest();
  }

  /**
   * Get last response.
   *
   * @return string
   *   Soap response.
   */
  public function getLastResponse() {
    return $this->soapClient->__getLastResponse();
  }

  /**
   * Set database ID.
   *
   * @param string $id
   *   Tripolis database ID.
   */
  public function setDbId($id) {
    $this->dbId = $id;
  }

  /**
   * Set workspace ID.
   *
   * @param string $id
   *   Tripolis workspace ID.
   */
  public function setWorkspaceId($id) {
    $this->workspaceId = $id;
  }

  /**
   * Function to print an error.
   *
   * @param object $e
   *   The error array.
   */
  protected function throwError($e) {
    $this->debug($e, ' error');
    if ($this->debug) {
      print '<div style="color: red; background: pink; padding: 5px 20px;">SOAP Fault: Code ' . $e->detail->errorResponse->errors->error->errorCode . ' -- ' . $e->detail->errorResponse->errors->error->message . '</div>';
    }
  }

  /**
   * Debug handler.
   *
   * Checks if we are 'inside' drupal, so we can use dsm.
   * Else, it checks for a debug function to use.
   *
   * @param mixed $value
   *   Message of object etc.
   * @param string $label
   *   Label to print.
   */
  protected function debug($value, $label = '') {
    if ($this->debug) {
      // @codingStandardsIgnoreStart
      // Used for debugging.
      if (function_exists('dsm')) {
        dsm($value, $label);
      }
      // @codingStandardsIgnoreEnd
      elseif (function_exists('drupal_set_message')) {
        // We are loaded inside drupal, don't use debug function in drupal.
        global $user;
        if ($user->uid == 1) {
          drupal_set_message(t('Debug mode is used in finalist_tripolis_API, but devel module is turned off.'));
        }
      }
      elseif (function_exists('debug')) {
        // Use debug function from other code, if any.
        debug($value, $label);
      }
    }
  }

  /**
   * Get first N characters of string.
   *
   * @param string $string
   *   String to cut.
   * @param int $length
   *   Number of characters.
   *
   * @return string
   *   Result.
   */
  public function trimLength($string, $length = 40) {
    return (strlen($string) > $length) ? substr($string, 0, $length) : $string;
  }

}
