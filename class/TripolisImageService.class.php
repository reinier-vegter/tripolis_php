<?php
/**
 * @file
 * Class to fetch image url for article.
 */

/**
 * Class TripolisImageService.
 */
class TripolisImageService extends TripolisAPISoap {

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
    parent::__construct($client, $username, $password);
    $this->setupSoap('ImageService');
  }

  /**
   * Fetch image.
   *
   * @param string $id
   *   Image ID.
   *
   * @return string|bool
   *   URL, or FALSE on failure.
   */
  public function getImageFromId($id) {
    try {
      $param = array(
        'getByIdRequest' => array(
          'id' => $id,
          'includeContent' => '0',
        ),
      );
      if ($result = $this->call('getById', $param)) {
        return $result['image']['publicUrl'];
      }
    }
    catch (SoapFault $e) {
      $this->throwError($e);
    }
    return FALSE;
  }

  /**
   * Upload image to Tripolis.
   *
   * @param string $path
   *   Path to image.
   * @param string $label
   *   Label to assign in Tripolis.
   *
   * @return mixed
   *   ID of image entity in Tripolis, or FALSE on failure.
   */
  public function create($path, $label = '') {
    if (empty($label)) {
      $label = basename($path);
    }

    // Corrrect imageType property.
    // Only this is allowed:
    // JPEG
    // PNG
    // GIF
    $image_type = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
    if ($image_type == 'JPG') {
      $image_type = 'JPEG';
    }

    $name = uniqid();
    try {
      $param = array(
        'createRequest' => array(
          'workspaceId' => $this->workspaceId,
          'label' => $this->trimLength($label),
          'name' => $this->trimLength($name),
          'imageType' => $image_type,
          'content' => file_get_contents($path),
        ),
      );
      $this->debug($param, 'params to send image');
      $result = $this->call('create', $param);
      if (is_array($result)) {
        return $result['id'];
      }
      elseif (is_object($result)) {
        // SoapFault.
        $this->throwError($result);
      }
      $this->debug($result, 'image call result.');
      $this->debug($this->getLastRequest(), 'last response');
    }
    catch (SoapFault $e) {
      $this->throwError($e);
    }
    return FALSE;
  }

}
