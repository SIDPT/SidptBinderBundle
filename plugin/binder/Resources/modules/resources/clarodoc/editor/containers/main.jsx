import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {actions as formActions} from '#/main/app/content/form/store/actions'
import {actions as modalActions} from '#/main/app/overlays/modal/store'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'



import {DocumentEditorMain as DocumentEditorMainComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/editor/components/main'

import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store'

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
      path: resourcesSelectors.path(state),
      currentContext: {
        type:"workspace",
        data:resourcesSelectors.workspace(state),
        resourceNode:formSelect.data(formSelect.form(state, selectors.FORM_NAME)).resourceNode,
        lastUpdate:getUpdateDate(formSelect.data(formSelect.form(state, selectors.FORM_NAME)).resourceNode)
      }
    }),
    (dispatch) => ({
      update(field, value) {
        dispatch(formActions.updateProp(selectors.FORM_NAME, field, value))
      }

    })
  )(DocumentEditorMainComponent)
)

export {
  DocumentEditorMain
}
