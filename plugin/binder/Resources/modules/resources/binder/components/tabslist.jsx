import React from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON} from '#/main/app/buttons'
import {CallbackButton} from '#/main/app/buttons/callback/components/button'


import {Tab as TabTypes} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/prop-types'

const TabsList = props => 
	<nav className="binder-nav">
	    {props.tabs.length > 0 && props.tabs.map((tab,index) =>
	      <CallbackButton
	        key={tab.id || 'new_tab'}
	        className={classes('nav-tab', {
	          'nav-tab-hidden': get(tab, 'restrictions.hidden')
	        })}
	        style={{
	          backgroundColor: get(tab, 'metadata.backgroundColor'),
	          borderColor: get(tab, 'metadata.borderColor'),
	          color: get(tab, 'metadata.textColor')
	        }}
	        callback={()=>{props.onClick(index)}}
	      >
	        {tab.metadata.icon &&
	          <span className={classes('fa fa-fw', `fa-${tab.metadata.icon}`, tab.title && 'icon-with-text-right')} />
	        }
	        {tab.title || tab.resourceNode.name || trans('new_tab')}
	      </CallbackButton>
	    )}

	    {props.create &&
	      <Button
	        className="nav-add-tab"
	        type={CALLBACK_BUTTON}
	        icon="fa fa-fw fa-plus"
	        label={trans('add_tab', {}, 'home')}
	        tooltip="bottom"
	        callback={props.create}
	      />
	    }
	  </nav>

TabsList.propTypes = {
  prefix: T.string,
  tabs: T.arrayOf(T.shape(
    TabTypes.propTypes
  )),
  onClick:T.func,
  create: T.func
}

TabsList.defaultProps = {
  prefix: ''
}

export {
	TabsList
}
