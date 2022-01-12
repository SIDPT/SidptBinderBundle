import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'

import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourceSelectors} from '#/main/core/resource/store'

import {DocumentPlayerMain as DocumentPlayerMainComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/components/main'
import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store'

import {selectors as securitySelectors} from '#/main/app/security/store'

import {actions as listActions} from '#/main/app/content/list/store'
import {selectors as resourcesToolSelectors} from '#/main/core/tools/resources/store'

import {PlayerMain as DirectoryPlayerMainComponent} from '#/main/core/resources/directory/player/components/main'

import {displayDate} from '#/main/app/intl/date'

const getUpdateDate = (resourceNode)=>{
  if(resourceNode && resourceNode.meta && resourceNode.meta.updated){
    return displayDate(resourceNode.meta.updated, false, true)
  }
  return "";
}

const DocumentPlayerMain = connect(
    (state) => ({
      document: formSelect.form(state, selectors.FORM_NAME).translated.clarodoc,
      path: resourceSelectors.path(state),
      resource: state['resource'],
      basePath: resourceSelectors.basePath(state),
      currentContext: {
        type:"workspace",
        data:resourceSelectors.workspace(state),
        resourceNode:formSelect.form(state, selectors.FORM_NAME).translated.resourceNode,
        lastUpdate:getUpdateDate(formSelect.data(formSelect.form(state, selectors.FORM_NAME)).resourceNode)
      }
    })
  )(DocumentPlayerMainComponent)

// directory style viewer
const DocumentDirectory = connect(
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
  DocumentPlayerMain,
  DocumentDirectory
}
