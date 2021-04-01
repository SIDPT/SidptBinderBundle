import {createSelector} from 'reselect'

const STORE_NAME = 'sidpt_binder'

const FORM_NAME = `${STORE_NAME}.binder`

const LIST_NAME = `${STORE_NAME}.resources`

const resource = (state) => state[STORE_NAME]




const binder = createSelector(
  [resource],
  (resource) => resource.binder
)

const directories = createSelector(
  [resource],
  (resource) => resource.directories
)

const directory = (dirs, directoryId) => {
  for (let i = 0; i < dirs.length; i++) {
    if (dirs[i].id === directoryId) {
      return dirs[i]
    } else if (dirs[i].children) {
      return directory(dirs[i].children, directoryId)
    }
  }

  return null
}

const id = createSelector(
  [binder],
  (binder) => binder.id
)

const title = createSelector(
  [binder],
  (binder) => binder.title
)

const tabs = createSelector(
  [binder],
  (binder) => binder.tabs
)


const tab = createSelector(
  [binder],
  (binder, index) => binder.tabs[index]
)


const playerDirectory = createSelector(
  [resource],
  (resource) => resource.directory
)

const listConfiguration = createSelector(
  [playerDirectory],
  (directory) => directory.list || {}
)


export const selectors = {
  STORE_NAME,
  FORM_NAME,
  LIST_NAME,
  resource,
  directories,
  binder,
  directory,
  playerDirectory,
  listConfiguration,
  id,
  title,
  tabs,
  tab
}
