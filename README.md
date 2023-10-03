# Chunker - A lightweight, glazing fast XML splitter written in PHP

The main goal of this library is to create chunks with predefined sizes from a big XML file (or to 'split' it into multiple chunks, so to say).

The algorithm was written using the XMLParser php library, which is capable of parsing an XML file line to line (or tag to tag) without state-control, and not by a string to string comparison or simple I/O operations. This attribute of the library makes it possible to implement validation on the said tags, everytime they are parsed.

## Usage
### Simple Chunking
The implementation is Object-oriented, so in order to split the files, an instance of Chunker has to be created first.

An example of a simple Chunker instance without validation, with **maximum 100 main tags**/chunk, and with outputfile names of *"out-{CHUNK}.xml"*:
```
$chunkSize = 100;
$outputFilePrefix = "out-";
$xmlfile = "bigFile.xml";
$validationFunction = fn($data, $tag) => {
    return true;
}
$checkingTags = array();

$chunker = new Chunker($xmlfile, $chunkSize, $outputFilePrefix, $validationFunction, $checkingTags);
```


### Constructor variables
The following table contains the parameters that can be (and should be) passed to the constructor.

| Parameter | Type | Description | Default value | Is required |
| --------- | ---- | ----------- | ------------- | ----------- |
| $xmlfile | string | The big XML file to be chunked | empty string | Yes |
| $chunkSize | int | The number of main tags maximum in a chunk | 100 | No |
| $outputFilePrefix | string | The prefix that will be used as the filename for the output chunks. Pattern: **'{outputFilePrefix}{CHUNK-NUMBER}.xml'** | 'out-' | No |
| $validationFunction | callable | The validator function that is used everytime a tag found, that is inside $checkingTags. If the tag data passes the validation, it will be included in the chunks, and will not be otherwise. It has to receive **two parameters**: first is the *data* that is inside the tag to be validated, and the second is the *tag* itself (both being strings). It has to **return a boolean**. | null | Yes |
| $checkingTags | array | An array of tags, where their data has to be validated using the $validationFunction callable. If we don't want any validation, we can pass an empty array to this parameter, or not specify it at all since it's not required. | empy array | No |

If any of the required parameters are empty/not specified, a Fatal error will be raised.

### Launch the chunking!

After you created an instance of Chunker, and all the parameters were set, you can start the chunking process. You can do this with the `Chunker::chunkXML` method. An example is shown below:
```
// ... the instance is created in $chunker
$chunker.chunkXML("item", "root");
```

This example will create xml chunks from the big file (if validation is enabled, then only the validated main tags will be included), with `$chunkSize` number of *main tags* (here it's called **"item"**). Every main tag is enclosed between one *root tag* (here it's called **"root"**) in every file (so every chunked file will contain **one root tag**, and `$chunkSize` number of **main tags inside** it).

THe method returns the logging session's string conversion (see below for more information).

## Logging

The class has an implemented logging feature. Everytime the `Chunker::chunkXML` is run, a new logging session is launched, which can be retrieved with the very same function. After its run, it returns the logging session converted into string:
```
// ... 
$log = $chunker.chunkXML(....);
echo $log;

/*

[timestamp] Starting new chunking...
[timestamp] ..
[timestamp] ..
*/

```
It is really helpful, when something is not working for your needs, and has to be debugged from step to step. **It is not neccessary to catch, so you can just call the function like its return value is void.**

## Examples

### Basic validation

Lets say, that you have an XML file (*"feed.xml"*) with a **Shop** root element, and multiple **shopItem** elements inside it (10.000+). You want it to split into files named *"feed-{chunk}.xml"* containing 1000 **shopItem**s maximum. And you also want to only include **shopItem**s, that has a *weight_kg* tag inside, which can only be greater than 10 (or '10 kgs'). The solution is like the following:

```
$chunkSize = 1000;
$xmlfile = "feed.xml";
$outPrefix = "feed-";
$checkingTags = array("weight_kg");
function validation($data, $tag) {
    if($tag == "weight_kg"){
        if(!empty($data) && intval($data) > 0) return true;
    }
    return false;
}

$mainTag = "shopItem";
$rootTag = "Shop";

$chunker = new Chunker($xmlfile, $chunkSize, $outPrefix, "validation", $chekingTags);
$chunker.chunkXML($mainTag, $rootTag);
```

