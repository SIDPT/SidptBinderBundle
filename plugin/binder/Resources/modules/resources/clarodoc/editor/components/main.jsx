import React, {Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

import {LINK_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {makeId} from '#/main/core/scaffolding/id'
import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {WidgetEditor} from '#/main/core/widget/editor/components/widget'
import {MODAL_WIDGET_CREATION} from '#/main/core/widget/editor/modals/creation'
import {MODAL_WIDGET_PARAMETERS} from '#/main/core/widget/editor/modals/parameters'

import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store/selectors'
 
class DocumentEditorMain extends Component {
  constructor(props) {
    super(props)

    this.state = {
      movingContentId: null
    }
  }

  startMovingContent(contentId) {
    this.setState({movingContentId: contentId})
  }

  stopMovingContent() {
    this.setState({movingContentId: null})
  }

  render() {

    const widgets = this.props.clarodoc.widgets;

    return (
      <Fragment>
        <FormData
            level={2}
            name={selectors.FORM_NAME}
            buttons={true}
            disabled={false}
            target={(clarodoc) => ['sidpt_document_update', {id: clarodoc.id}]}
            cancel={{
                type: LINK_BUTTON,
                target: this.props.path,
                exact: true
              }}
            sections={[
              {
                icon: 'fa fa-fw fa-plus',
                title: trans('general'),
                primary: true,
                fields: [
                  {
                    name: 'longTitle',
                    type: 'string',
                    label: trans('title'),
                    required: true
                  },{
                    name: 'centerTitle',
                    type: 'boolean',
                    label: trans('center_title')
                  }
                ]
              }
            ]} > 
          <div className="widgets-grid">
          { widgets.map((widgetContainer, index) => {

            return (
              <WidgetEditor
                key={index}
                widget={widgetContainer}
                currentContext={this.props.currentContext}
                isMoving={this.state.movingContentId}
                stopMovingContent={() => this.stopMovingContent()}
                startMovingContent={(contentId) => this.startMovingContent(contentId)}
                moveContent={(movingContentId, newParentId, position) => {
                  const newWidgets = cloneDeep(widgets)
                  let movingContentIndex = -1

                  let oldWidgets = null
                  //let oldParentId = null
                  let oldParent = null

                  this.props.clarodoc.widgets.forEach(widget => {
                      if (widget.contents.findIndex(content => content && content.id === movingContentId) > -1) {
                        oldWidgets = this.props.clarodoc.widgets;
                        //oldParentId = this.props.clarodoc.id;
                        movingContentIndex = widget.contents.findIndex(content => content && content.id === movingContentId)
                      }
                    });

                  if (oldWidgets && -1 !== movingContentIndex) {
                    oldWidgets = newWidgets
                    
                    oldWidgets.forEach(widget => {
                      if (widget.contents.findIndex(content => content && content.id === movingContentId) > -1) {
                        oldParent = widget;
                      }
                    });
                   
                    const newParent = newWidgets.find(widget => widget.id === newParentId)
                    // generate a new id for moved content for save simplicity
                    const newContent = cloneDeep(oldParent.contents[movingContentIndex]);
                    newContent.id = makeId();
                    newParent.contents[position] = newContent

                    // removes the content to delete and replace by null
                    oldParent.contents[movingContentIndex] = null

                    this.props.update('widgets', newWidgets)
                    //this.props.update('widgets', oldWidgets, oldParentId)
                  }

                  this.stopMovingContent();
                }}
                update={(widget) => {
                  // copy array
                  const newWidgets = widgets.slice(0)
                  // replace modified widget
                  newWidgets[index] = widget
                  // propagate change
                  this.props.update('widgets', newWidgets)
                }}
                actions={[
                  {
                    type: MODAL_BUTTON,
                    icon: 'fa fa-fw fa-plus',
                    label: trans('add_section_before'),
                    modal: [MODAL_WIDGET_CREATION, {
                      create: (widget) => {
                        // copy array
                        const newWidgets = widgets.slice(0)
                        // insert element
                        newWidgets.splice(index, 0, widget) // insert element

                        // propagate change
                        this.props.update('widgets', newWidgets)
                      }
                    }]
                  }, {
                    type: CALLBACK_BUTTON,
                    icon: 'fa fa-fw fa-arrow-up',
                    label: trans('move_top', {}, 'actions'),
                    disabled: 0 === index,
                    callback: () => {
                      // copy array
                      const newWidgets = widgets.slice(0)

                      // permute widget with the previous one
                      const movedWidget = newWidgets[index]
                      newWidgets[index] = newWidgets[index - 1]
                      newWidgets[index - 1] = movedWidget
                      // propagate change
                      this.props.update('widgets', newWidgets)
                    }
                  }, {
                    type: CALLBACK_BUTTON,
                    icon: 'fa fa-fw fa-arrow-down',
                    label: trans('move_bottom', {}, 'actions'),
                    disabled: widgets.length - 1 === index,
                    callback: () => {
                      // copy array
                      const newWidgets = widgets.slice(0)

                      // permute widget with the next one
                      const movedWidget = newWidgets[index]
                      newWidgets[index] = newWidgets[index + 1]
                      newWidgets[index + 1] = movedWidget

                      // propagate change
                      this.props.update('widgets', newWidgets)
                    }
                  }, {
                    type: MODAL_BUTTON,
                    icon: 'fa fa-fw fa-cog',
                    label: trans('configure', {}, 'actions'),
                    modal: [MODAL_WIDGET_PARAMETERS, {
                      widget: widgetContainer,
                      save: (widget) => {
                        // copy array
                        const newWidgets = widgets.slice(0)
                        // replace modified widget
                        newWidgets[index] = widget
                        // propagate change
                        this.props.update('widgets', newWidgets)
                      }
                    }]
                  }, {
                    type: CALLBACK_BUTTON,
                    icon: 'fa fa-fw fa-trash-o',
                    label: trans('delete', {}, 'actions'),
                    dangerous: true,
                    confirm: {
                      title: trans('section_delete_confirm_title'),
                      message: trans('section_delete_confirm_message'),
                      subtitle: widgets[index].name
                    },
                    callback: () => {
                      const newWidgets = widgets.slice(0) // copy array
                      newWidgets.splice(index, 1) // remove element
                      this.props.update('widgets', newWidgets)
                    }
                  }
                ]}
              />
            )
          })}

          {0 === widgets.length &&
            <ContentPlaceholder
              size="lg"
              icon="fa fa-frown-o"
              title={trans('no_section')}
            />
          }

          <Button
              className="btn btn-block btn-emphasis btn-add-section component-container"
              type={MODAL_BUTTON}
              label={trans('add_section')}
              modal={[MODAL_WIDGET_CREATION, {
                create: (widget) => this.props.update('widgets',
                  widgets.concat([widget]) // copy array & append element
                )Ï
              }]}
              primary={true}
            />
          </div>
        </FormData>
      </Fragment> )
  
  }
}
    


DocumentEditorMain.propTypes = {
  path: T.string.isRequired,
  clarodoc: T.object.isRequired,
  currentContext: T.object.isRequired,
  update: T.func.isRequired
}

export {
  DocumentEditorMain
}

/**
 * 
 */