import React from 'react'
import {PropTypes as T} from 'prop-types'
import omit from 'lodash/omit'

import {Routes} from '#/main/app/router'

import {MenuSection} from '#/main/app/layout/menu/components/section'

import {BinderPlayerMenu} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/player/containers/menu'
import {BinderEditorMenu} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/editor/containers/menu'

/*
<Routes
      path={props.path}
      routes={[
        {
          path: '/edit',
          disabled: !props.editable,
          render() {
            const Menu = (
              <BinderEditorMenu autoClose={props.autoClose} />
            )
            
            return Menu
          }
        }, {
          path: '/',
          render() {
            const Menu = (
              <BinderPlayerMenu autoClose={props.autoClose} />
            )

            return Menu
          }
        }
      ]}
    />
*/
const BinderMenu = props => {

  return (
    <MenuSection
      {...omit(props, 'binder')}
      title={props.binder.title}
    >
    
    </MenuSection>
  );
}
  


BinderMenu.propTypes = {
  binder:T.object.isRequired,
  path: T.string.isRequired,
  editable: T.bool.isRequired,

  // from menu
  opened: T.bool.isRequired,
  toggle: T.func.isRequired,
  autoClose: T.func.isRequired
}

export {
  BinderMenu
}
