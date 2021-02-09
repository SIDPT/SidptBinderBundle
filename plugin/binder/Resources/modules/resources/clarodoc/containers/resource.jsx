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
import {actions as resourcesActions, selectors as resourcesSelectors} from '#/main/core/resource/store'


const DocumentResource = withRouter(
  withReducer(selectors.STORE_NAME, reducer)(
    connect(
      (state) => ({
		resourceNode:resourcesSelectors.resourceNode(state),
		clarodoc: formSelect.originalData(formSelect.form(state, selectors.FORM_NAME)),
		canEdit: hasPermission('edit', resourcesSelectors.resourceNode(state)),
		currentUser: securitySelectors.currentUser(state),
		// tool params
		basePath:resourcesSelectors.basePath(state),
		contextType: toolSelectors.contextType(state),
		// resource params
		embedded:resourcesSelectors.embedded(state),
		showHeader:resourcesSelectors.showHeader(state),
		managed:resourcesSelectors.managed(state),
		userEvaluation:resourcesSelectors.resourceEvaluation(state),
		accessErrors:resourcesSelectors.accessErrors(state)
      }),
    (dispatch) => ({
      reload() {
        dispatch(resourcesActions.setResourceLoaded(false))
      },
      dismissRestrictions() {
        dispatch(resourcesActions.dismissRestrictions(true))
      },
      checkAccessCode(resourceNode, code, embedded = false) {
        dispatch(resourcesActions.checkAccessCode(resourceNode, code, embedded))
      }
    })
    )(DocumentResourceComponent)
  )
);

export {
  DocumentResource
}
