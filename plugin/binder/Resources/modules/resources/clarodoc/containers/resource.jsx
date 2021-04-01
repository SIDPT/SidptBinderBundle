import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {withReducer} from '#/main/app/store/components/withReducer'

import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {hasPermission} from '#/main/app/security'


import {DocumentResource as DocumentResourceComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/components/resource'
import {reducer, selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store'

// resources
import {selectors as securitySelectors} from '#/main/app/security/store'
import {selectors as toolSelectors} from '#/main/core/tool/store'
import {actions as resourceActions, selectors as resourceSelectors} from '#/main/core/resource/store'


const DocumentResource = withRouter(
  withReducer(selectors.STORE_NAME, reducer)(
    connect(
      (state) => ({
        editable: hasPermission('edit', resourceSelectors.resourceNode(state)),
        resourceNode:resourceSelectors.resourceNode(state),
        path: resourceSelectors.basePath(state),
        clarodoc: formSelect.data(formSelect.form(state, selectors.FORM_NAME))
      }),
    (dispatch) => ({
      reload() {
        dispatch(resourceActions.setResourceLoaded(false))
      },
      dismissRestrictions() {
        dispatch(resourceActions.dismissRestrictions(true))
      },
      checkAccessCode(resourceNode, code, embedded = false) {
        dispatch(resourceActions.checkAccessCode(resourceNode, code, embedded))
      }
    })
    )(DocumentResourceComponent)
  )
);



export {
  DocumentResource
}
