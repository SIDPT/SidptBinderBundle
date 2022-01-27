import React, {Component} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON} from '#/main/app/buttons'
import {CallbackButton} from '#/main/app/buttons/callback/components/button'


import {Tab as TabTypes} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/prop-types'

class TabsList extends Component{
	constructor(props){
		super(props);

		this.state = {
			currentTab:0
		}

		this.changeTab = this.changeTab.bind(this);
	}

	changeTab(newIndex){
		this.setState({
			currentTab:newIndex
		});
		if(this.props.onClick){
			this.props.onClick(newIndex);
		}
	}


	render(){
		return (
			<nav className="binder-nav">
				{this.props.tabs.length > 0 && this.props.tabs.map(
					(tab,index) => {
						const style = this.state.currentTab === index ? 
							{ }:{
								backgroundColor: get(tab, 'display.backgroundColor'),
								borderColor: get(tab, 'display.borderColor'),
								color: get(tab, 'display.textColor')
							};

						return (
							<CallbackButton
									key={tab.id || 'new_tab'}
									className={classes('nav-tab', {
										'nav-tab-hidden': get(tab, 'restrictions.hidden'),
										'active': this.state.currentTab === index
									})}
									style={style}
									callback={()=>{this.changeTab(index)}}>
								{tab.metadata.icon &&
									<span className={classes(
											'fa fa-fw', 
											`fa-${tab.metadata.icon}`, 
											tab.title && 'icon-with-text-right')} />
								}
								{tab.title || (tab.resourceNode && tab.resourceNode.name) || trans('new_tab')}
							</CallbackButton>);
					}
						
					)}

				{this.props.create &&
					<Button
					className="nav-add-tab"
					type={CALLBACK_BUTTON}
					icon="fa fa-fw fa-plus"
					label={trans('add_tab', {}, 'home')}
					tooltip="bottom"
					callback={this.props.create}
					/>
				}
			</nav>);

	}
}

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
