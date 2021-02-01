import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'

import {hasPermission} from '#/main/app/security'

import {BinderMenu as BinderMenuComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/components/menu'
import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/store'

const BinderMenu = withRouter(
  connect(
    (state) => ({
      binder: formSelect.originalData(formSelect.form(state, selectors.STORE_NAME+'.binder')),
      path:resourcesSelectors.path(state),
      editable: hasPermission('edit', resourcesSelectors.resourceNode(state))
    })
  )(BinderMenuComponent)
)

export {
  BinderMenu
}
