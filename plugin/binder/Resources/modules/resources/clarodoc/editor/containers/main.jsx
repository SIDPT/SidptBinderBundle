import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {actions as formActions} from '#/main/app/content/form/store/actions'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'
import {selectors as toolSelectors} from '#/main/core/tool/store'

import {DocumentEditorMain as DocumentEditorMainComponent} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/editor/components/main'

import {selectors} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/store'


const DocumentEditorMain = withRouter(
  connect(
    (state) => ({
      clarodoc: formSelect.data(formSelect.form(state, selectors.FORM_NAME)),
      path: resourcesSelectors.path(state),
      currentContext: toolSelectors.context(state)
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
