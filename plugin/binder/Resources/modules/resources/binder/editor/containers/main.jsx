import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {actions as formActions} from '#/main/app/content/form/store/actions'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'

import {BinderEditorMain as BinderEditorMainComponent} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/editor/components/main'

import {selectors} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/store'


const BinderEditorMain = withRouter(
  connect(
    (state) => ({
      data: formSelect.data(formSelect.form(state, selectors.FORM_NAME)),
      path: resourcesSelectors.path(state),
      currentContext: {
        type:"workspace",
        data:resourcesSelectors.workspace(state)
      }
    }),
    (dispatch) => ({
      update(field, value) {
        dispatch(formActions.updateProp(selectors.FORM_NAME, field, value))
      },
      updateTab(index, field, value) {
        dispatch(formActions.updateProp(
          `${selectors.FORM_NAME}.tabs[${index}]`, 
          field, 
          value))
      },
      save(binder_id){
        dispatch(
          formActions.save(
            selectors.FORM_NAME,
            ['sidpt_binder_update', {id: binder_id}]
        ));
      }
    })
  )(BinderEditorMainComponent)
)

export {
  BinderEditorMain
}
