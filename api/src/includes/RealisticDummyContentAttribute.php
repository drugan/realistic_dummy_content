<?php

namespace Drupal\realistic_dummy_content_api\includes;

use Drupal\realistic_dummy_content_api\cms\CMS;

/**
 * Represents either a field or a property for an entity.
 *
 * Fields are for example field_image, or field_body, and attributes are
 * for example the title of a node and the image of a user.
 *
 * We want to abstract away the differences so we can treat both
 * the same way without using control statements in our code.
 */
abstract class RealisticDummyContentAttribute {
  /**
   * The entity is set on construction and is a subclass of
   * RealisticDummyContentEntityBase. It contains information about the
   * entity to which this field instance is attached.
   */
  private $entity;

  /**
   * The name of this attribuet, for example title, picture, field_image...
   */
  private $name;

  /**
   * Constructor.
   *
   * @param object $entity
   *   Object of a subclass of RealisticDummyContentEntityBase.
   * @param string $name
   *   The name of the field, for example body or picture or field_image
   */
  function __construct($entity, $name) {
    $this->entity = $entity;
    $this->name = $name;
  }

  /**
   * Getter for $this->name.
   */
  function GetName() {
    return $this->name;
  }

  /**
   * Getter for $this->entity.
   */
  function GetEntity() {
    return $this->entity;
  }

  /**
   * Returns a pseudo-random number.
   *
   * The number should be the same for the same entity, so we need to know the
   * entity.
   *
   * @return int
   *   A random or sequential number.
   */
  function rand($start, $end) {
    return $this->GetEntity()->rand($start, $end);
  }

  /**
   * Returns the appropriate environment, real or testing.
   */
  function env() {
    return $this->GetEntity()->env();
  }

  /**
   * Gets the bundle of the associated entity.
   *
   * @return string
   *   The bundle name.
   */
  function GetBundle() {
    return $this->GetEntity()->GetBundle();
  }

  /**
   * Gets the UID of the associated entity.
   *
   * @return int
   *   The UID.
   */
  function GetUid() {
    return $this->GetEntity()->GetUid();
  }

  /**
   * Get the entity type of the associated entity.
   *
   * @return string
   *   The entity type as a string, 'node' or 'user' for example.
   */
  function GetEntityType() {
    return $this->GetEntity()->GetType();
  }

  /**
   * Returns the type of this attribute.
   *
   * Drupal uses fields (managed by the field system) and properties to define
   * attributes of entities. Fields include body and field_image; properties include
   * title and the user picture.
   *
   * @return string
   *   'property' or 'field'
   */
  abstract function GetType();

  /**
   * Changes this attribute by looking for data in files.
   *
   * Any module can define a file hierarchy to determine realistic dummy data
   * for this attribute. See the ./realistic_dummy_content/ folder for an
   * example.
   *
   * This function checks the filesystem for compatible files (for example, only
   * image files are acceptable candidate files for field_image), choose one
   * through the selection mechanism (random or sequential), and then procedes to
   * change the data for the associated field for this class.
   */
  function Change() {
    $files = $this->GetCandidateFiles();
    CMS::debug('Found ' . count($files) . ' files which have realistic dummy data.');
    $this->ChangeFromFiles($files);
  }

  /**
   * Given candidate files, change value of this attribute based on one of them.
   *
   * @param array $files
   *   An array of files.
   */
  function ChangeFromFiles($files) {
    $value = $this->ValueFromFiles($files);
    if ($value === NULL) {
      // NULL indicates we could not find a value with which to replace the
      // current value. The value can still be '', or FALSE, etc.
      return;
    }
    $entity = $this->GetEntity()->GetEntity();
    CMS::setEntityProperty($entity, $this->GetName(), $value);
    $this->GetEntity()->SetEntity($entity);
  }

  /**
   * Get acceptable file extensions which contain data for this attribute.
   *
   * For example, title attributes can be replaced by data in txt files, whereas
   * picture and field_image attributes require png, jpg, gif.
   *
   * @return array
   *   An array of acceptable file extensions.
   */
  function GetExtensions() {
    // By default, use only text files. Other manipulators, say, for image fields
    // or file fields, can specify other extension types.
    return array('txt');
  }

  /**
   * Get all candidate files for a given field for this entity.
   */
  function GetCandidateFiles() {
    $files = array();
    $filepaths = array();
    foreach (CMS::moduleList() as $module) {
      $filepath = DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . '/realistic_dummy_content/fields/' . $this->GetEntityType() . '/' . $this->GetBundle() . '/' . $this->GetName();
      $filepaths[] = $filepath;
      $files = array_merge($files, RealisticDummyContentEnvironment::GetAllFileGroups($filepath, $this->GetExtensions()));
    }
    CMS::debug($filepaths, 'Searching in');
    return $files;
  }

  /**
   * Given a RealisticDummyContentFileGroup object, get structured property.
   *
   * The structured property can then be added to the entity.
   *
   * For example, sometimes the appropriate property is array('value' => 'abc',
   * 'text_format' => 'filtered_html'); other times is it just a string.
   * Subclasses will determine what to do with the contents from the file.
   *
   * @param object $file
   *   The actual file object.
   *
   * @return NULL|array
   *   In case of an error or if the value does not apply or is empty, return
   *   NULL; otherwise returns structured data to be added to the entity object.
   */
  function ValueFromFile($file) {
    try {
      if (in_array($file->GetRadicalExtension(), $this->GetExtensions())) {
        return $this->ValueFromFile_($file);
      }
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Given a RealisticDummyContentFileGroup object, get a structured property.
   *
   * This function is not meant to called directly; rather, call
   * ValueFromFile(). This function must be overriden by subclasses.
   *
   * @param object $file
   *   An object of type RealisticDummyContentFileGroup.
   *
   * @return NULL|array
   *   Returns structured data to be added to the entity object, or NULL if such
   *   data can't be creatd.
   *
   * @throws
   *   Exception.
   */
  protected abstract function ValueFromFile_($file);

  /**
   * Given a list of files, return a value from one of them.
   *
   * @param array $files
   *   An array of file objects
   *
   * @return mixed
   *   A file object or array, or an associative array with the keys "value" and
   *   "format", or NULL if there are no files to choose from or the files have
   *   the wrong extension.
   */
  function ValueFromFiles($files) {
    try {
      if (count($files)) {
        $rand_index = $this->rand(0, count($files) - 1);
        $file = $files[$rand_index];
        return $this->ValueFromFile($file);
      }
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Return acceptable image file extensions.
   *
   * @return array
   *   An array of extension for image files.
   */
  function GetImageExtensions() {
    return array('gif', 'png', 'jpg');
  }

  /**
   * Return acceptable text file extensions.
   *
   * @return array
   *   An array of extension for text files.
   */
  function GetTextExtensions() {
    return array('txt');
  }

  /**
   * Return an image file object if possible.
   *
   * @param object $file
   *   The RealisticDummyContentFileGroup object
   *
   * @return NULL|object
   *   NULL if the file is not an image, or if an error occurred; otherwise a
   *   Drupal file object.
   */
  function ImageSave($file) {
    try {
      $exists = $file->Value();
      if (!$exists) {
        throw new RealisticDummyContentException('Please check if the file exists before attempting to save it');
      }
      $return = NULL;
      if (in_array($file->GetRadicalExtension(), $this->GetImageExtensions())) {
        $return = $this->FileSave($file);
        $alt = $file->Attribute('alt');
        if ($alt) {
          $return->alt = $alt;
        }
      }
      return $return;
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Return a file object.
   *
   * @param object $file
   *   The original file, a RealisticDummyContentFileGroup object.
   *
   * @return object
   *   A file object.
   *
   * @throws
   *   Exception.
   */
  function FileSave($file) {
    $drupal_file = $file->GetFile();
    if (!$drupal_file) {
      throw new RealisticDummyContentException('Please check if the file exists before attempting to save it');
    }
    $uri = $drupal_file->uri;
    // $random = md5($uri) . rand(1000000000, 9999999999);
    // DO NOT RENAME FOR TESTING.
    $random = $file->GetRadical();
    $drupal_file = $this->env()->file_save_data($file->Value(), 'public://dummyfile' . $random . '.' . $file->GetRadicalExtension());
    $drupal_file->uid = $this->GetUid();
    $return = CMS::fileSave($drupal_file);

    if (!is_object($return)) {
      throw new \Exception('Internal error, ' . __FUNCTION__ . ' expecting to return an object');
    }

    return $return;
  }

}
