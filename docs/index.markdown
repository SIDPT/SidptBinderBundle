---
# Feel free to add content and custom Front Matter to this file.
# To modify the layout, see https://jekyllrb.com/docs/themes/#overriding-theme-defaults
title: Document Plugin for Claroline LMS
layout: home
---

The SIDPTBinderBundle holds the "binder" plugin of the IPIP Platform for its fork of the Claroline Connect LMS.
This bundle exposes 2 new resources to the platform :
- A Document resource, to create and centralised one or more pages using claroline widgets
- A Binder resource, to regroup resources under a single tabbed document (developement has been put on hold)


## Document resource

The Document resource re-uses Claroline home pages principles, with its WYSIWYG editor, but generalize it to make it as a resource.

This resource has 3 display mode :
- The original mode display the list of widgets as a single page
- A paginated mode allows to split the document in pages, each pages holding a section of widgets.
- A directory view to display subresources stored under the document in the claroline resource tree.


## Binder resource

(on stand by)
A Binder is a resource that regroup other resources displayed within a set of ordered tabs.