import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {PageBreadcrumb} from '#/main/app/page/'

import {Button} from '#/main/app/action/components/button'

import {CallbackButton} from '#/main/app/buttons/callback/components/button'

class BinderNavigator extends Component {
	constructor(props){
		super(props);
  }

  render(){
	return (
		<nav className="binder-nav">
			
			{this.props.parent && 
				<Fragment>
				<CallbackButton
						key={this.props.parent.id}
						className={classes('nav-tab', {
							'nav-tab-hidden': get(this.props.parent, 'restrictions.hidden')
						})}
						style={{
							backgroundColor: get(this.props.parent, 'display.backgroundColor'),
							borderColor: get(this.props.parent, 'display.borderColor'),
							color: get(this.props.parent, 'display.textColor')
						}}
						callback={()=>{props.onTabSelected(this.props.parent)}}>
					{this.props.parent.display.icon &&
						<span className={classes('fa fa-fw', `fa-${this.props.parent.display.icon}`, this.props.parent.title && 'icon-with-text-right')} />
					}
					{this.props.parent.title || this.props.parent.resourceNode.name} <span className="visually-hidden">{trans('upper_sections')}</span>
				</CallbackButton>
				<div>
					<p> &gt; <span className="visually-hidden">{trans('section_content')}:</span></p>
				</div>
				</Fragment>
			}

			{this.props.tabs.length > 0 && this.props.tabs.map((tab,index) => {
				let style = this.props.selectedSlugPath === tab.slug ?
					{
	          backgroundColor: get(tab, 'display.backgroundColor'),
	          borderColor: get(tab, 'display.borderColor'),
	          color: get(tab, 'display.textColor')
	        } : {};
				return (
					<CallbackButton
			        key={tab.id || 'new_tab'}
			        className={classes('nav-tab', {
			          'nav-tab-hidden': get(tab, 'restrictions.hidden')
			        })}
			        style={style}
			        callback={()=>{this.props.onTabSelected(tab)}}>
		        {tab.display.icon &&
		          <span className={classes('fa fa-fw', `fa-${tab.display.icon}`, tab.title && 'icon-with-text-right')} />
		        }
		        {tab.title || tab.resourceNode.name || trans('new_tab')}
		      </CallbackButton>
	      )
			}
	      
	    )}

		
		</nav>
		);
  }
}

BinderNavigator.PropTypes = {
	parent:TabTypes.propTypes,
	tabs: T.arrayOf(T.shape(
		TabTypes.propTypes
	  )),
	selectedSlugPath:T.string,
	onTabSelected:T.func // should update the parent and tabs array
}
