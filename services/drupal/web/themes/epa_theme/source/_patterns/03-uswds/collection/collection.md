---
el: .usa-collection
title: Collection
state: inreview
---
See
[https://designsystem.digital.gov/components/collection/]().

__Variables:__
* modifier_classes: [string] Classes to modify the default component styling.
* collection: [array] List of collection items. Each item is an object containing:
  * show_calendar: [boolean] Whether to display the calendar.
  * datetime: [string] HTML datetime of item. (YYYY-MM-DDThh:mm:ssTZD)
  * month: [string] Three letter representation of month.
  * day: [string] Day of collection item.
  * image_src: [string] Image source of the collection image.
  * image_alt: [string] Al attribute for collection image.
  * url: [string] URL of collection item.
  * heading: [string] Heading of collection item.
  * subtitle: [string] Subtitle of collection item.
  * description: [string] Description of collection item.
  * more_info: [array] List of more information. Each item is an object containing:
    * text: [string] Title of the item.
  * tags: [array] List of tags. Each item is an object containing:
    * title: [string] Title of the item.
    * type: [string] Type of the tag (e.g., new).
