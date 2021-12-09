import {createSelector} from 'reselect'

const STORE_NAME = 'sidpt_document'

const FORM_NAME = `${STORE_NAME}.clarodoc`

const LIST_NAME = `${STORE_NAME}.resources`

const resource = (state) => state[STORE_NAME]


const clarodoc = createSelector(
  [resource],
  (resource) => resource.clarodoc
)

const level = createSelector(
  [resource],
  (resource) => resource.level
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
  [clarodoc],
  (clarodoc) => clarodoc.id
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
  clarodoc,
  level,
  directories,
  directory,
  id,
  playerDirectory,
  listConfiguration
}
