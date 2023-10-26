<?php 

namespace Chunker;
use \Datetime;
use \XMLParser;

if(!class_exists("Chunker")){

    /**
     * A lightweight, fast, and optimized XML file splitter with build in tag data validation, written with the XMLParser library. The main goal of this is to split an XML file into multiple small chunks (hence the name), then save it into multiple different little XML files, so that slower servers, plugins etc can process XML files with more than even 10.000+ records. It is built on XMLParser, a powerful php xml processing library. 
     * 
     * MINIMUM PHP VERSION: 7.4
     * 
     * @author Borsodi Gergő
     * @version 2.0.1
     * @link https://github.com/borsodigerii/php-xml-chunker
     * 
     */
    class Chunker{

        /**
         * The name of the file to be processed.
         * @var string
         */
        private string $xmlFile;

        /**
         * The maximum chunksize.
         * @var int
         */
        private int $chunkSize;

        /**
         * Counter for the chunks.
         * @var int
         */
        private int $CHUNKS;

        /**
         * The data that will be written into a chunk.
         * @var string
         */
        private string $PAYLOAD = '';

        /**
         * The data used for one iteration of the main tag.
         * @var string
         */
        private string $PAYLOAD_TEMP = '';

        /**
         * A container used to implement validation.
         * @var
         */
        private string $DATA_BETWEEN = '';

        /**
         * The root tag of the yet-to-process xml file.
         * @var string
         */
        private string $rootTag;

        /**
         * The charset used for the decoding/encoding process.
         * @var string
         */
        private string $CHARSET;

        /**
         * The prefix used for the output files.
         * @var string
         */
        private string $outputFilePrefix;

        /**
         * Counter for the items put into one chunk.
         * @var int
         */
        private int $ITEMCOUNT = 0;

        /**
         * The main tag, of which defines one item in the chunking.
         * @var string
         */
        private string $CHUNKON;

        /**
         * A variable used for logging.
         * @var string
         */
        private string $log = "";

        /**
         * The total number of processed main tags.
         * @var int
         */
        private int $totalItems = 0;

        /**
         * A variable that indicates if a maintag that doesn't satisfy the validation has been found.
         * @var bool
         */
        private bool $excludedItemFound = false;

        /**
         * A variable to indicate that the next data that will be read, has to be validated since its opening tag is present in $checkingTags.
         * @var bool         
         */
        private bool $checkNextData = false;

        /**
         * A variable that carries the tagname of the data that is about to be validated.
         * @var string
         */
        private string $checkNextDataTag = '';

        /**
         * An array of tags, where their data has to be validated runtime.
         * @var array
         */
        private array $checkingTags = array();

        /**
         * A callback function, that processes the validation. Has to be a callable.
         * @var callable
         */
        private $passesValidation;

        /**
         * The constructor of the class, it creates an instance of Chunker.
         * 
         * @param string $xmlfile The path of the xml file
         * @param int $chunkSize The number of which every little/chunked file should maximum contain from the main XML tag specified lated. **Default: 100**
         * @param string $outputFilePrefix The name that will be the prefix for the chunk's filenames. The pattern is the following: *{outputFilePrefix}{CHUNK_NUMBER}.xml* **Default: 'out-'.** Example files with the default prefix: 'out-1.xml', 'out-2.xml' etc
         * @param callable $validationFunction The validator function to be run every time the parser has found a tag, that is in $checkingTags. If it did, it runs the validator through the tag, and if the function returned **true** (so the tag data was *valid*), it includes it in the chunk, otherwise ignores it. The validator function has to return **bool**, and cannot be **null**. If it is null, a Fatal error will be raised. The passed callback HAS to have the following parameters:
         * - $data: string, the currently processed tag data (what is inside the tag) will be inside this parameter
         * - $tag: string, the currently processed tagname will be inside this parameter
         * @param array $checkingTags This array consists of tagnames where the data inside the tag has to be validated. It can be empty, and can be omitted, if no validation is required (not like the validator function, which HAS to be provided through here, otherwise an error will be raised)
         * @return void A new Chunker is generated.
         */
        public function __construct(string $xmlfile = "", int $chunkSize = 100, string $outputFilePrefix = 'out-', callable $validationFunction = null, array $checkingTags = array())
        {
            if(empty($xmlfile)) trigger_error("[Chunker] Fatal error: no XML file/empty filestring specified in __construct.", E_USER_ERROR);
            if(!$validationFunction) trigger_error("[Chunker] Fatal error: no callback handler specified for validation checks.", E_USER_ERROR);
            $this->checkingTags = $checkingTags;
            $this->passesValidation = $validationFunction;
            $this->xmlFile = $xmlfile;
            $this->chunkSize = $chunkSize;
            $this->CHUNKS = 0;
            $this->outputFilePrefix = $outputFilePrefix;
        }

        /**
         * This function processes a whole chunk (max size <= $chunkSize) by writing the **PAYLOAD** into a chunkfile, and resetting all stationary variables.
         * @param bool $lastChunk Indicates if the current is the last chunk in the file. Sometimes if its not indicated, and it is the last chunk, the closing tag is not always present.
         * @return void
         */
        private function processChunk($lastChunk = false) {
            $this->logging("Writing new chunk..");
            if ('' == $this->PAYLOAD) {
                $this->logging("Empty PAYLOAD. Returning.");
                return;
            }
            $xp = fopen($file = $this->outputFilePrefix . "" . $this->CHUNKS . ".xml", "w");
            fwrite($xp, '<?xml version="1.0" encoding="'.strtolower($this->CHARSET).'"?>'."\n");
                fwrite($xp, '<'.$this->rootTag.' xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">');
                    fwrite($xp, $this->PAYLOAD);
                $lastChunk || fwrite($xp, "</".$this->rootTag.">");
            fclose($xp);
            $this->logging("Written {$file}");
            $this->CHUNKS++;
            $this->PAYLOAD = '';
            $this->PAYLOAD_TEMP = '';
            $this->DATA_BETWEEN = '';
            $this->excludedItemFound = false;
            $this->checkNextData = false;
            $this->checkNextDataTag = '';
            $this->ITEMCOUNT  = 0;
        }

        /**
         * A handler function used by the parser for starting elements. It checks if the currently parsed tag is present in the $checkingTags array, and sets some stationary variables if a validation needs to be done.
         * @param XMLParser $xml The parser
         * @param string $tag the currently parsed tag
         * @param array $attrs An array of attributes of the tag. We dont use it here, so it is only there for syntax purposes
         */
        private function startElement($xml, $tag, $attrs = array()) {
            if(in_array($tag, $this->checkingTags)){
                // checkable tag found
                $this->checkNextData = true;
                $this->checkNextDataTag = $tag;
            }
            $this->PAYLOAD_TEMP .= "<{$tag}>";
        }

        /**
         * A handler function used by the parser for ending elements. It checks if the currently parsed main tag had any tags that were present in the $checkingTags array, and had data that couldn't have been validated. If true, the lastly parsed main element will be excluded from the chunking process, and will be written into a chunk file otherwise. If the processed main tag's number has reached the $chunkSize limit, a new chunk will be written to the disk.
         * @param XMLParser $xml The parser
         * @param string $tag the currently parsed tag
         */
        private function endElement($xml, $tag) {
            if($this->checkNextData && $this->checkNextDataTag == $tag){
                // ezt az adatot validalni kell
                if(!call_user_func($this->passesValidation, $this->DATA_BETWEEN, $this->checkNextDataTag)) $this->excludedItemFound = true;
                $this->checkNextData = false;
                $this->checkNextDataTag = '';
            }
            $this->dataHandler(null, "</{$tag}>");
            $this->DATA_BETWEEN = '';
            if ($this->CHUNKON == $tag) {
                $this->logging("Closing ".$this->CHUNKON." element found");

                if($this->excludedItemFound){
                    // volt nem passzolo item
                    $this->logging("Excluded item found, skipping current " .$this->CHUNKON."..");
                    $this->PAYLOAD_TEMP = '';
                    $this->DATA_BETWEEN = '';
                    $this->excludedItemFound = false;
                    $this->checkNextData = false;
                    $this->checkNextDataTag = '';
                    return;
                }
                $this->PAYLOAD .= $this->PAYLOAD_TEMP;
                $this->PAYLOAD_TEMP = '';
                $this->DATA_BETWEEN = '';
                $this->totalItems++;
                if (++$this->ITEMCOUNT >= $this->chunkSize) {
                    $this->logging("Chunk limit reached, printing chunk...");
                    $this->processChunk();
                }
            }
        }

        /**
         * A handler function used by the parser for data between tags. If the $checkNextData stationary property was set to true, then it means, that the currently parsed data has to be validated. It it did not pass the validation, the main element will be flagged as 'excluded from chunking', and will not be written to disk.
         * @param XMLParser $xml The parser
         * @param string $data The data to be handled
         */
        private function dataHandler($xml, $data) {
            $this->DATA_BETWEEN .= $data;
            $this->PAYLOAD_TEMP .= $data;
        }
        
        /**
         * A handler function, not used by this class, just for formal purposes.
         */
        private function defaultHandler($xml, $data) {
            // a.k.a. Wild Text Fallback Handler, or WTFHandler for short.
            $this->logging("WTF text found: " .$data);
        }

        /**
         * A helper function that creates the XML parser instance, sets the options for the parsing, and establishes the setup.
         * @param string $CHARSET The charset that will be used by the parser. **Default: "UTF-8"**
         * @param bool $bareXML Indicates if the incoming data is unformatted/maybe invalid XML. Not used in this class.
         * @return XMLParser The created parser instance
         */
        private function createXMLParser($CHARSET = "UTF-8", $bareXML = false) {
            $CURRXML = xml_parser_create($CHARSET);
            xml_parser_set_option( $CURRXML, XML_OPTION_CASE_FOLDING, false);
            xml_parser_set_option( $CURRXML, XML_OPTION_TARGET_ENCODING, $CHARSET);
            xml_set_element_handler($CURRXML, [$this, 'startElement'], [$this, 'endElement']);
            xml_set_character_data_handler($CURRXML, [$this, 'dataHandler']);
            xml_set_default_handler($CURRXML, [$this, 'defaultHandler']);
            if ($bareXML) {
                xml_parse($CURRXML, '<?xml version="1.0" encoding="'.$CHARSET.'"?>', 0);
            }
            $this->logging("Created XML Parser");
            return $CURRXML;
        }

        /**
         * A funcion to start the chunking process. It will initiate the parsint instance, and start the XML parsing, along with the chunking of the data in every specified $chunkSize intervals.
         * @param string $mainTag The tag of which will be used to count the number of main elements in a chunk. Usually the second-level XML tag in a document.
         * @param string $rootTag The root tag of which every other $mainTag is the children of. There is only one of this in an XML document (not the XML header, which is in the first row).
         * @param string $charset The character set used by the parser. **Default: UTF-8** Possible values: "UTF-8", "ISO-8859-1"
         * 
         * @return string The main log that was created during the chunking
         */
        public function chunkXML($mainTag = 'shopItem', $rootTag = 'Shop', $charset = "UTF-8") {
            $this->rootTag = $rootTag;
            $this->CHARSET = $charset;
            $this->CHUNKON = $mainTag;
            
            $this->logging("Starting new Chunking.*****", true);
            $this->logging("Internal encoding: " .print_r(iconv_get_encoding(), true));
            $xml = $this->createXMLParser($this->CHARSET, false);
            
            $fp = fopen($this->xmlFile, 'r');
            if(!$fp){
                trigger_error("Could not open XML file", E_USER_ERROR);
            }
            $this->logging("Opened XML File");
            $this->CHUNKS = 0;
            $this->totalItems = 0;
            $this->excludedItemFound = false;
            $this->checkNextData = false;
            $this->checkNextDataTag = '';
            $this->PAYLOAD = '';
            $this->PAYLOAD_TEMP = '';
            $this->DATA_BETWEEN = '';
            while(!feof($fp)) {
                $chunk = fgets($fp, 102400);
                $this->logging("Reading line: " .$chunk);
                if(!$chunk){
                    $this->logging("Reading new line failed, next try");
                }
                if(xml_parse($xml, $chunk, feof($fp)) == 0){
                    $this->logging("Could not parse line. Next try...");
                }
                
            }
            xml_parser_free($xml);
   
            // Now, it is possible that one last chunk is still queued for processing.
            $this->processChunk(true);
            $this->logging("Internal encoding: " .print_r(iconv_get_encoding(), true));
            $this->logging("Ended chunking. Total processed '" .$this->CHUNKON."' objects: " .$this->totalItems);
            return $this->log;
        }
        /**
         * Used for administrative purposes. A message can be logged into the internal logging variable, and then later be returned/passed back as value by some functions.
         * @param string $msg The message to be logged
         * @param bool $start Indicates if the logging has to be started over (so the past logged messages will be deleted, and a cleared loggin variable will be set). **Default: false**
         */
        public function logging($msg, $start = false){

            if($start){
                $this->log = "[" .(new DateTime())->format("y:m:d h:i:s"). "] " .$msg. "\n\r";
                return;
            }
            $this->log .= "[" .(new DateTime())->format("y:m:d h:i:s"). "] " .$msg. "\n\r";

        }
    }

}


// This function is for backward compatibility (in PHPv7.4 str_contains function was not yet implemented in the standard library)
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

?>