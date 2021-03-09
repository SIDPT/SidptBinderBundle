# SIDPT Binder plugin for Claroline LMS

This repo holds the binder plugin of the IPIP platform for the claroline LMS.

## New ressources

This plugin expose 2 new ressources to users of the IPIP platform :
- A new "Document" ressource can be used to create pages that holds claroline widgets, like the existing home tool tabs.
- A new "Binder" ressource that can be used to create a tabbed document using other "documents" or "binder" resources

## Targeted functionnalities

### Document resource

For learning user : 
- Use or point to integrated resources like lessons or quizz
- Possibly track progression in the document :
  - Get progression for widgets when available
  - Check the user position in the document :
  	- On leaving the document, mark it as validate if the user has finished all the widgets and has reached the bottom of the document

For content editors / resources manager: 
- WYSIWYG creation tool to structure the page, including:
  - Creating sections
  - Adding widgets (that is pointing to resources) to sections
  - Moving widgets in and between sections
  - Moving sections in the page


### Binder
A binder should be associated with a progression status