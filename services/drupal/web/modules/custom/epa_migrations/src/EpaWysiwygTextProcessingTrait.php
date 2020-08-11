<?php

namespace Drupal\epa_migrations;

use DOMDocument;

/**
 * Helpers to reformat/strip inline HTML.
 */
trait EpaWysiwygTextProcessingTrait {

  /**
   * Transform 'related info box' in wysiwyg content.
   *
   * @param string $wysiwyg_content
   *   The content to search and transform inline html.
   *
   * @return string
   *   The original wysiwyg_content with transformed inline html.
   */
  public function processText($wysiwyg_content) {

    $pattern = '/';
    $pattern .= 'class=".*?(box multi related-info).*?"|';
    $pattern .= 'class=".*?(pagetop).*?"|';
    $pattern .= 'class=".*?(exit-disclaimer).*?"|';
    $pattern .= 'class=".*?(tabs).*?"|';
    $pattern .= 'href=".*?(exitepa).*?"|';
    $pattern .= '(need Adobe Reader to view)|(need a PDF reader to view)';
    $pattern .= '/';

    $matches = [];

    if (preg_match_all($pattern, $wysiwyg_content, $matches) > 0) {
      // Add a temp wrapper around the wysiwyg content.
      $wysiwyg_content = '<?xml encoding="UTF-8"><tempwrapper>' . $wysiwyg_content . '</tempwrapper>';

      // Load the content as a DOMDocument for more powerful transformation.
      $doc = new \DomDocument();
      libxml_use_internal_errors(TRUE);
      $doc->loadHtml($wysiwyg_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT);
      libxml_clear_errors();

      // Run the document through the transformation methods depending on the
      // matches identified.
      foreach ($matches as $key => $match_strings) {

        // Skip the first value, which contains the full pattern matches.
        if ($key > 0) {
          // Get unique values with array_unique, then remove any empty strings
          // with array_filter, and finally get the remaining match text.
          $match = array_pop(array_filter(array_unique($match_strings)));

          switch ($match) {
            case 'box multi related-info':
              $doc = $this->transformRelatedInfoBox($doc);
              break;

            case 'pagetop':
              $doc = $this->stripPageTopLinks($doc);
              break;

            case 'exit-disclaimer':
              $doc = $this->stripExitEpaLinks($doc);
              break;

            case 'exitepa':
              $doc = $this->stripExitEpaLinks($doc);
              break;

            case 'tabs':
              $doc = $this->stripTabClasses($doc);
              break;

            case 'need Adobe Reader to view':
              $doc = $this->stripPdfDisclaimers($doc);
              break;

            case 'need a PDF reader to view':
              $doc = $this->stripPdfDisclaimers($doc);
              break;
          }
        }
      }

      // Transform the document back to HTML.
      $wysiwyg_content = $doc->saveHtml();

      // Remove the temp wrapper and encoding from the output.
      return str_replace([
        '<?xml encoding="UTF-8">',
        '<tempwrapper>',
        '</tempwrapper>',
      ], '', $wysiwyg_content);
    }

    return $wysiwyg_content;
  }

  /**
   * Transform Related Info Box.
   *
   * @param \DOMDocument $doc
   *   The document to search and replace.
   *
   * @return \DOMDocument
   *   The document with transformed info boxes.
   */
  private function transformRelatedInfoBox(DOMDocument $doc) {

    // Create a DOM XPath object for searching the document.
    $xpath = new \DOMXPath($doc);

    $related_info_boxes = $xpath->query('//div[contains(@class, "box multi related-info")]');

    if ($related_info_boxes) {
      foreach ($related_info_boxes as $key => $rib_wrapper) {
        // Replace div classes on box wrapper.
        $box_classes = [
          'box multi related-info',
          'right',
          'left',
          'clear-right',
          'clear-left',
        ];

        $box_replacement_classes = [
          'box box--related-info',
          'u-align-right',
          'u-align-left',
          'u-clear-right',
          'u-clear-left',
        ];

        $wrapper_classes = $rib_wrapper->attributes->getNamedItem('class')->value;
        $wrapper_classes = str_replace($box_classes, $box_replacement_classes, $wrapper_classes);
        $rib_wrapper->setAttribute('class', $wrapper_classes);

        // Change child H2 to div and replace classes.
        $h2 = $xpath->query('//h2[contains(@class, "pane-title")]', $rib_wrapper)[0];
        if ($h2) {
          $box_title = $doc->createElement('div', $h2->nodeValue);
          $box_title_classes = $h2->attributes->getNamedItem('class')->value;
          $box_title_classes = str_replace('pane-title', 'box__title', $box_title_classes);
          $box_title->setAttribute('class', $box_title_classes);
          $h2->parentNode->replaceChild($box_title, $h2);
        }

        // Replace div class on pane content.
        $box_content = $xpath->query('//div[contains(@class, "pane-content")]', $rib_wrapper)[0];
        if ($box_content) {
          $box_content_classes = $box_content->attributes->getNamedItem('class')->value;
          $box_content_classes = str_replace('pane-content', 'box__content', $box_content_classes);
          $box_content->setAttribute('class', $box_content_classes);
        }

        // Replace the original element with the modified element in the doc.
        $rib_wrapper->parentNode->replaceChild($rib_wrapper, $related_info_boxes[$key]);
      }
    }

    return $doc;

  }

  /**
   * Strip Top of Page links.
   *
   * @param \DOMDocument $doc
   *   The document to search and replace.
   *
   * @return \DOMDocument
   *   The document with stripped links.
   */
  private function stripPageTopLinks(DOMDocument $doc) {
    // Create a DOM XPath object for searching the document.
    $xpath = new \DOMXPath($doc);

    $page_top_links = $xpath->query('//*[contains(concat(" ", @class, " "), " pagetop ")]');

    if ($page_top_links) {
      foreach ($page_top_links as $link) {
        // Delete the element and any parent elements that are now empty.
        $element_to_remove = $this->determineElementToRemove($link);
        $element_to_remove->parentNode->removeChild($element_to_remove);
      }
    }

    return $doc;
  }

  /**
   * Strip Exit EPA link disclaimers.
   *
   * @param \DOMDocument $doc
   *   The document to search and replace.
   *
   * @return \DOMDocument
   *   The document with stripped links.
   */
  private function stripExitEpaLinks(DOMDocument $doc) {
    // Create a DOM XPath object for searching the document.
    $xpath = new \DOMXPath($doc);

    // Links that use the exit-disclaimer class.
    $exit_epa_links = $xpath->query('//*[contains(concat(" ", @class, " "), " exit-disclaimer ") or contains(@href, "exit-epa") or contains(@href, "exitepa")]');

    if ($exit_epa_links) {
      foreach ($exit_epa_links as $link) {
        // Delete the element and any parent elements that are now empty.
        $element_to_remove = $this->determineElementToRemove($link);
        $element_to_remove->parentNode->removeChild($element_to_remove);
      }
    }

    return $doc;
  }

  /**
   * Strip PDF disclaimers.
   *
   * @param \DOMDocument $doc
   *   The document to search and replace.
   *
   * @return \DOMDocument
   *   The document with stripped disclaimers.
   */
  private function stripPdfDisclaimers(DOMDocument $doc) {
    // Create a DOM XPath object for searching the document.
    $xpath = new \DOMXPath($doc);

    // Elements that include the PDF disclaimer.
    $pdf_disclaimer_elements = $xpath->query('//*[contains(text(), "need Adobe Reader to view") or contains(text(), "need a PDF reader to view")]');

    if ($pdf_disclaimer_elements) {
      foreach ($pdf_disclaimer_elements as $element) {
        // Delete the element and any parent elements that are now empty.
        $element_to_remove = $this->determineElementToRemove($element);
        $element_to_remove->parentNode->removeChild($element_to_remove);
      }
    }

    return $doc;

  }

  /**
   * Strip Tab classes.
   *
   * @param \DOMDocument $doc
   *   The document to search and replace.
   *
   * @return \DOMDocument
   *   The document with stripped tab classes.
   */
  private function stripTabClasses(DOMDocument $doc) {
    // Create a DOM XPath object for searching the document.
    $xpath = new \DOMXPath($doc);

    // Tab elements.
    $tabs_parent_element = $xpath->query('//div[@id="tabs"]');
    if (count($tabs_parent_element) == 0) {
      $tabs_parent_element = $xpath->query('//ul[contains(concat(" ", @class, " "), " tabs ")]');
    }

    if (count($tabs_parent_element) > 0) {
      foreach ($tabs_parent_element as $parent_element) {
        if ($parent_element->tagName == 'div') {
          $parent_element->removeAttribute('id');
          $uls = $xpath->query('//ul[contains(concat(" ", @class, " "), " tabs ") or @id="tabsnav"]', $parent_element);
          foreach ($uls as $ul) {
            $ul->setAttribute('class', str_replace('tabs', '', $ul->attributes->getNamedItem('class')->value));
            if ($ul->attributes->getNamedItem('id')->value == 'tabsnav') {
              $ul->removeAttribute('id');
            }
          }
        }
        else {
          $parent_element->setAttribute('class', str_replace('tabs', '', $parent_element->attributes->getNamedItem('class')->value));
          if ($parent_element->attributes->getNamedItem('id')->value == 'tabsnav') {
            $parent_element->removeAttribute('id');
          }
        }

        $lis = $xpath->query('//li[contains(concat(" ", @class, " "), " active ")]', $parent_element);
        foreach ($lis as $li) {
          $li->setAttribute('class', str_replace('active', '', $li->attributes->getNamedItem('class')->value));
        }

        $links = $xpath->query('//a[contains(concat(" ", @class, " "), " menu-internal ")]', $parent_element);
        foreach ($links as $link) {
          $link->setAttribute('class', str_replace('menu-internal', '', $link->attributes->getNamedItem('class')->value));
        }

      }
    }

    return $doc;

  }

  /**
   * Remove an element's white-space only child nodes.
   *
   * @param \DOMElement|\DOMDocument $element
   *   The element to have its child elements cleaned.
   *
   * @return \DOMElement
   *   The element with cleaned children.
   */
  private function removeEmptyTextNodes($element) {
    $num_children = count($element->childNodes);
    if ($num_children > 1) {
      $empty_text_nodes = [];
      foreach ($element->childNodes as $node) {
        if ($node->nodeType == 3 && trim($node->nodeValue) == '') {
          $empty_text_nodes[] = $node;
        }
      }

      if ($empty_text_nodes) {
        foreach ($empty_text_nodes as $node) {
          $node->parentNode->removeChild($node);
        }
      }
    }
    return $element;
  }

  /**
   * Traverse ancestor tree of an element to determine if it is an only child.
   *
   * @param \DOMElement|\DOMDocument $element
   *   The element to have its ancestors checked.
   *
   * @return \DOMElement
   *   The top-most ancestor that has no children other than the element.
   */
  private function determineElementToRemove($element) {

    // Initially the element to remove is the original one.
    $element_to_remove = $element;

    // Find any ancestor elements that only contain this element.
    // Start by seeing if the immediate parent has any other children.
    $cleaned_parent = $this->removeEmptyTextNodes($element->parentNode);
    if (count($cleaned_parent->childNodes) == 1 && $cleaned_parent->childNodes[0]->isSameNode($element)) {
      $only_child = TRUE;
      $element_to_remove = $cleaned_parent;
    }
    else {
      $only_child = FALSE;
    }

    // If the original element is an only child, traverse the ancestors.
    while ($only_child && $element->name !== 'tempwrapper') {
      $cleaned_parent = $this->removeEmptyTextNodes($element_to_remove->parentNode);

      if (count($cleaned_parent->childNodes) == 1 && $cleaned_parent->childNodes[0]->isSameNode($element_to_remove)) {
        $only_child = TRUE;
        $element_to_remove = $element_to_remove->parentNode;
      }
      else {
        $only_child = FALSE;
      }
    }

    return $element_to_remove;
  }

}
