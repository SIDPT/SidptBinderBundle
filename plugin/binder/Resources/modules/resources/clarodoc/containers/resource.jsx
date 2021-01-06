import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {withReducer} from '#/main/app/store/components/withReducer'

import {selectors as resourcesSelectors} from '#/main/core/resource/store'
import {hasPermission} from '#/main/app/security'

//import {selectors as toolSelectors} from '#/main/core/tool/store'


import {DocumentResource as DocumentResourceComponent} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/components/resource'
import {reducer, selectors} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/store'


const DocumentResource = withRouter(
  withReducer(selectors.STORE_NAME, reducer)(
    connect(
      (state) => ({
        canEdit: hasPermission('edit', resourcesSelectors.resourceNode(state))
      })
    )(DocumentResourceComponent)
  )
);

export {
  DocumentResource
}
