import React from 'react'
import {PropTypes as T} from 'prop-types'
import omit from 'lodash/omit'

import {MenuSection} from '#/main/app/layout/menu/components/section'

const DocumentMenu = props =>
  <MenuSection
    {...omit(props, 'clarodoc')}
    title={props.clarodoc.title}
  />;


DocumentMenu.propTypes = {
  clarodoc:T.object.isRequired,

  // from menu
  opened: T.bool.isRequired,
  toggle: T.func.isRequired,
  autoClose: T.func.isRequired
}

export {
  DocumentMenu
}
