import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'

import {selectors as formSelect} from '#/main/app/content/form/store/selectors'

import {selectors as securitySelectors} from '#/main/app/security/store'
import {actions as listActions} from '#/main/app/content/list/store'
import {selectors as resourcesToolSelectors} from '#/main/core/tools/resources/store'
import {selectors as resourceSelectors} from '#/main/core/resource/store'

import {PlayerMain as DirectoryPlayerMainComponent} from '#/main/core/resources/directory/player/components/main'

import {BinderPlayerMain as BinderPlayerMainComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/player/components/main'

import {actions, selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/store'

const BinderPlayerMain = withRouter(
  connect(
    (state) => ({
      binder: formSelect.originalData(formSelect.form(state, selectors.FORM_NAME)).binder,
      displayedTabs:selectors.displayedTabs(state),
      displayedDocument:selectors.displayedDocument(state),
      currentContext: {
        type:"workspace",
        data:resourceSelectors.workspace(state)
      }
    }),
    (dispatch) => ({
      getBinderTabContent(tab) {
        dispatch(actions.getBinderTabContent(tab))
      },
      resetBinder(binder){
        dispatch(actions.reset(binder))
      }
    })
  )(BinderPlayerMainComponent)
)

// directory style viewer
const BinderDirectory = connect(
  (state) => ({
    path: resourceSelectors.basePath(state), // the base path without current resource id
    currentUser: securitySelectors.currentUser(state),
    embedded: resourceSelectors.embedded(state),
    rootNode: resourcesToolSelectors.root(state),
    currentNode: resourceSelectors.resourceNode(state),
    listName: selectors.LIST_NAME,
    listConfiguration: selectors.listConfiguration(state),
    storageLock: false
  }),
  (dispatch) => ({
    updateNodes() {
      dispatch(listActions.invalidateData(selectors.LIST_NAME))
    },

    deleteNodes() {
      dispatch(listActions.invalidateData(selectors.LIST_NAME))
    }
  })
)(DirectoryPlayerMainComponent)


export {
  BinderPlayerMain,
  BinderDirectory
}
