import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {withReducer} from '#/main/app/store/components/withReducer'

import {selectors as resourcesSelectors} from '#/main/core/resource/store'
import {hasPermission} from '#/main/app/security'

import {actions as formActions} from '#/main/app/content/form/store/actions'
import {BinderResource as BinderResourceComponent} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/components/resource'
import {reducer, selectors} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/store'


const BinderResource = withRouter(
  withReducer(selectors.STORE_NAME, reducer)(
    connect(
      (state) => ({
        editable: hasPermission('edit', resourcesSelectors.resourceNode(state)),
        resourceNode:resourcesSelectors.resourceNode(state),
        path: resourcesSelectors.basePath(state)
      }),
     (dispatch) => ({
      update(field, value) {
        dispatch(formActions.updateProp(selectors.FORM_NAME, field, value))
      },
      loadSection(binder,path) {
        // update breadcrumb
        // load selected binder or document to the view
      }
    })
    )(BinderResourceComponent)
  )
);

export {
  BinderResource
}
