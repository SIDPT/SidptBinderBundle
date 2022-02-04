import {connect} from 'react-redux'
import {API_REQUEST, url} from '#/main/app/api'
import {withRouter} from '#/main/app/router'
import {actions as formActions} from '#/main/app/content/form/store/actions'
import {actions as modalActions} from '#/main/app/overlays/modal/store'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourceSelectors} from '#/main/core/resource/store'



import {DocumentEditorMain as DocumentEditorMainComponent} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/editor/components/main'

import {selectors} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/store'

import {displayDate} from '#/main/app/intl/date'

const getUpdateDate = (resourceNode)=>{
  if(resourceNode && resourceNode.meta && resourceNode.meta.updated){
    return displayDate(resourceNode.meta.updated, false, true)
  }
  return "";
}

const DocumentEditorMain = withRouter(
  connect(
    (state) => ({
      data:formSelect.data(formSelect.form(state, selectors.FORM_NAME)),
      path: resourceSelectors.path(state),
      currentContext: {
        type:"workspace",
        data:resourceSelectors.workspace(state),
        //resourceNode:formSelect.data(formSelect.form(state, selectors.FORM_NAME)).resourceNode,
        //lastUpdate:getUpdateDate(formSelect.data(formSelect.form(state, selectors.FORM_NAME)).resourceNode)
        resourceNode:resourceSelectors.resourceNode(state),
        lastUpdate:getUpdateDate(resourceSelectors.resourceNode(state))
      }
    }),
    (dispatch) => ({
      update(field, value) {
        dispatch(formActions.updateProp(selectors.FORM_NAME, field, value))
      },
      moveWidgetToDocumentNode(widgetContainer, fromDocument, toNode ){
        dispatch({
          [API_REQUEST]: {
            url: ['sidpt_document_move_section', {
              widgetContainerId: widgetContainer.id,
              fromId:fromDocument.id,
              toId:toNode.id
            }],
            request: {
              method: 'PUT'
            },
            success: (data) => {
              dispatch(formActions.updateProp(selectors.FORM_NAME, 'clarodoc', data.clarodoc))
            }
          }
        })
      }


    })
  )(DocumentEditorMainComponent)
)

export {
  DocumentEditorMain
}
