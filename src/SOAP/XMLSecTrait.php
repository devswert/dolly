<?php

namespace Devswert\Dolly\SOAP;

use DOMXPath;
use DOMElement;
use DOMDocument;
use Exception;

trait XMLSecTrait{
	public function sortAndAddAttrs($element, $arAtts) {
	    $newAtts = array();
	    foreach ($arAtts AS $attnode) {
	        $newAtts[$attnode->nodeName] = $attnode;
	    }
	    ksort($newAtts);
	    foreach ($newAtts as $attnode) {
	        $element->setAttribute($attnode->nodeName, $attnode->nodeValue);
	    }
	}

	public function canonical($tree, $element, $withcomments) {
	    if ($tree->nodeType != XML_DOCUMENT_NODE) {
	        $dom = $tree->ownerDocument;
	    } else {
	        $dom = $tree;
	    }
	    if ($element->nodeType != XML_ELEMENT_NODE) {
	        if ($element->nodeType == XML_DOCUMENT_NODE) {
	            foreach ($element->childNodes AS $node) {
	                $this->canonical($dom, $node, $withcomments);
	            }
	            return;
	        }
	        if ($element->nodeType == XML_COMMENT_NODE && !$withcomments) {
	            return;
	        }
	        $tree->appendChild($dom->importNode($element, TRUE));
	        return;
	    }
	    $arNS = array();
	    if ($element->namespaceURI != "") {
	        if ($element->prefix == "") {
	            $elCopy = $dom->createElementNS($element->namespaceURI, $element->nodeName);
	        } else {
	            $prefix = $tree->lookupPrefix($element->namespaceURI);
	            if ($prefix == $element->prefix) {
	                $elCopy = $dom->createElementNS($element->namespaceURI, $element->nodeName);
	            } else {
	                $elCopy = $dom->createElement($element->nodeName);
	                $arNS[$element->namespaceURI] = $element->prefix;
	            }
	        }
	    } else {
	        $elCopy = $dom->createElement($element->nodeName);
	    }
	    $tree->appendChild($elCopy);

	    /* Create DOMXPath based on original document */
	    $xPath = new DOMXPath($element->ownerDocument);

	    /* Get namespaced attributes */
	    $arAtts = $xPath->query('attribute::*[namespace-uri(.) != ""]', $element);

	    /* Create an array with namespace URIs as keys, and sort them */
	    foreach ($arAtts AS $attnode) {
	        if (array_key_exists($attnode->namespaceURI, $arNS) &&
	                ($arNS[$attnode->namespaceURI] == $attnode->prefix)) {
	            continue;
	        }
	        $prefix = $tree->lookupPrefix($attnode->namespaceURI);
	        if ($prefix != $attnode->prefix) {
	            $arNS[$attnode->namespaceURI] = $attnode->prefix;
	        } else {
	            $arNS[$attnode->namespaceURI] = NULL;
	        }
	    }
	    if (count($arNS) > 0) {
	        asort($arNS);
	    }

	    /* Add namespace nodes */
	    foreach ($arNS AS $namespaceURI => $prefix) {
	        if ($prefix != NULL) {
	            $elCopy->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:" . $prefix, $namespaceURI);
	        }
	    }
	    if (count($arNS) > 0) {
	        ksort($arNS);
	    }

	    /* Get attributes not in a namespace, and then sort and add them */
	    $arAtts = $xPath->query('attribute::*[namespace-uri(.) = ""]', $element);
	    sortAndAddAttrs($elCopy, $arAtts);

	    /* Loop through the URIs, and then sort and add attributes within that namespace */
	    foreach ($arNS as $nsURI => $prefix) {
	        $arAtts = $xPath->query('attribute::*[namespace-uri(.) = "' . $nsURI . '"]', $element);
	        sortAndAddAttrs($elCopy, $arAtts);
	    }

	    foreach ($element->childNodes AS $node) {
	        $this->canonical($elCopy, $node, $withcomments);
	    }
	}

	/*
	 * @author OrangePeople Software Ltda <soporte@orangepeople.cl>
	 * helper function 
	 * Modification by Hermann Alexander Arriagada Méndez
	 * for IssuerSerial
	 */
	public function getIssuerName($X509Cert) {
	    /* $handler = fopen($X509Cert, "r");
	      $cert = fread($handler, 8192);
	      fclose($handler); */
	    $cert = $X509Cert;
	    $cert_as_array = openssl_x509_parse($cert);
	    $name = $cert_as_array['name'];
	    $name = str_replace("/", ",", $name);
	    $name = substr($name, 1, strlen($name));
	    return $name;
	}

	/*
	 * @author OrangePeople Software Ltda <soporte@orangepeople.cl>
	 * helper function 
	 * Modification by Hermann Alexander Arriagada Méndez
	 * for IssuerSerial
	 */
	public function getSerialNumber($X509Cert) {
	    /* $handler = fopen($X509Cert, "r");
	      $cert = fread($handler, 8192);
	      fclose($handler); */
	    $cert = $X509Cert;
	    $cert_as_array = openssl_x509_parse($cert);
	    $serialNumber = $cert_as_array['serialNumber'];
	    return $serialNumber;
	}

	/*
	  $element - DOMElement for which to produce the canonical version of
	  $exclusive - boolean to indicate exclusive canonicalization (must pass TRUE)
	  $withcomments - boolean indicating wether or not to include comments in canonicalized form
	 */
	public function C14NGeneral($element, $exclusive = FALSE, $withcomments = FALSE) {
	    /* IF PHP 5.2+ then use built in canonical functionality */
	    $php_version = explode('.', PHP_VERSION);
	    if (($php_version[0] > 5) || ($php_version[0] == 5 && $php_version[1] >= 2)) {
	        return $element->C14N($exclusive, $withcomments);
	    }

	    /* Must be element or document */
	    if (!$element instanceof DOMElement && !$element instanceof DOMDocument) {
	        return NULL;
	    }
	    /* Currently only exclusive XML is supported */
	    if ($exclusive == FALSE) {
	        throw new Exception("Only exclusive canonicalization is supported in this version of PHP");
	    }

	    $copyDoc = new DOMDocument();
	    $this->canonical($copyDoc, $element, $withcomments);
	    return $copyDoc->saveXML($copyDoc->documentElement, LIBXML_NOEMPTYTAG);
	}
}