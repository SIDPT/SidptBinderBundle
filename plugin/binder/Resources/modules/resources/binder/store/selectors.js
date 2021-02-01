import {createSelector} from 'reselect'

const STORE_NAME = 'sidpt_binder'

const FORM_NAME = `${STORE_NAME}.binder`



const resource = (state) => state[STORE_NAME]

const binder = createSelector(
  [resource],
  (resource) => resource.binder
)


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

export const selectors = {
  STORE_NAME,
  FORM_NAME,
  resource,
  binder,
  id,
  title,
  tabs,
  tab
}
