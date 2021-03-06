<?php


/**
 * Created: March 14th 2019
 * @author winters
 *Last modified: April 24nd 2019
 * Last modified by: selina winter
 * 
 * This class creates a Library object that holds the LibCal lib value
 * and new specialHours to be added on My Business.
 * 
 */
class Library {
//variables
    var $lib;
    public $specialHoursArray = array(); 
 
   
    public function _construct(){
   
    }
    
    //member functions

    
    /**
     * This function sets the SpecialHours array
     * 
     * @param unknown $newHours Google SpecialHours array
     */
    function setSpecialHours($newHours){
        $this->specialHoursArray = $newHours;
        
    }
    
    /**
     * This function returns the lib string
     * 
     * @return string
     */
    function getLib(){
        return $this->lib;
    }
    
    /**
     * This function sets the lib string.
     * 
     * @param string $name
     */
    function setLib($name){
        $this->lib = $name;
    }
    
 
    
  /**
   * This function returns the specialHours array
   * 
   * @return array|unknown
   */
  public  function getSpecialHoursArray(){
      
        return $this->specialHoursArray;
        
    }
    
    /**
     * This function returns a particular index value from the specialHours array
     * 
     * @param integer $index
     */
    function getOneSpecialHoursArray($index){
        return $this->specialHoursArray[$index];
    }
    
    /**
     * This function adds a new specialHoursPeriod to the end of the array.
     * 
     * @param unknown $newHours Google SpecialHourPeriod
     */
    public function setOneSpecialHoursArray($newHours){
        array_push($this->specialHoursArray, $newHours);
     
    }

   
}