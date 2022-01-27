import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CallbackButton} from '#/main/app/buttons/callback/components/button'


import {Tab as TabType, Binder as BinderType} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/prop-types'

class BinderNavigator extends Component {
	constructor(props){
		super(props);
	}

	render(){
		let crumbPathPart = this.props.displayedContentSlug.split("/")
		// Remove document part of the slug
		crumbPathPart = crumbPathPart.slice(0, crumbPathPart.length - 1)
		const tabStack = [];
		let currentLevelTabs = this.props.binder.tabs;
		crumbPathPart.forEach((slug) => {
			currentLevelTabs.every((tab) => {
				if(tab.slug == slug){
					tabStack.push(tab)
					currentLevelTabs = tab.content.tabs;
				}
				return true;
			})
		})
		const crumbTabs = tabStack.map(
			(tab,index) => {
				return (
					<CallbackButton
							key={tab.id}
							className={classes('nav-crumb')}
							style={{
								backgroundColor: get(tab, 'display.backgroundColor'),
								borderColor: get(tab, 'display.borderColor'),
								color: get(tab, 'display.textColor')
							}}
							callback={()=>{
								if(index !== tabStack.length - 1 && this.props.onTabSelected) {
									this.props.onTabSelected(tab);
								}}}>
						{tab.display.icon &&
							<span className={classes(
									'fa fa-fw', 
									`fa-${tab.display.icon}`, 
									tab.title && 'icon-with-text-right')} />
						}
						{tab.title || (tab.resourceNode && tab.resourceNode.name)}
						{index === tabStack.length - 1 && 
							<span className="visually-hidden">{trans('current_section')}</span>}
					</CallbackButton>
				)
			}
		) || [];

		// Current tabs
		const tabs = this.props.displayedTabs.length > 0 ? this.props.displayedTabs : this.props.binder.tabs;
		return (
			<Fragment>
				{crumbTabs && crumbTabs.length > 0 &&
					<nav className="binder-nav" aria-label="Breadcrumb">
						<CallbackButton
								key={this.props.binder.id}
								className={classes('nav-crumb')}
								callback={()=>{
									if(this.props.onBinderSelected){
									 	this.props.onBinderSelected(this.props.binder);
									}
								}}>
							|<span className="visually-hidden">{trans('binder_root')}</span>
						</CallbackButton>
						{crumbTabs}
					</nav>
				}
				{tabs.length > 0 && 
					<nav className="binder-nav"> {
						tabs.map(
							(tab,index) => {
								if( tab.display.visible && 
										(tab.title || 
											(tab.resourceNode && tab.resourceNode.name))){
									let style = this.props.displayedContentSlug === tab.slug ?
										{} : {
											backgroundColor: get(tab, 'display.backgroundColor'),
											borderColor: get(tab, 'display.borderColor'),
											color: get(tab, 'display.textColor')
										};
									
									return (
										<CallbackButton
												key={tab.id}
												className={classes('nav-tab', {
													'nav-tab-hidden': !get(tab, 'display.visible'),
													'active': this.props.displayedContentSlug === tab.slug
												})}
												style={style}
												callback={()=>{
													if(this.props.onTabSelected){
													 	this.props.onTabSelected(tab);
													}
												}}>
											{tab.display.icon &&
												<span className={classes(
													'fa fa-fw', 
													`fa-${tab.display.icon}`, 
													tab.title && 'icon-with-text-right')} />
											}
											{tab.title || (tab.resourceNode && tab.resourceNode.name)}
										</CallbackButton>
									);
								}
							}
						)
					}</nav>
				}
			</Fragment>
		);
	}
}

BinderNavigator.propTypes = {
	binder:T.shape(BinderType.propTypes), // root binder with its tab tree
	displayedTabs:T.arrayOf(T.object), // option props controled
	displayedContentSlug:T.string,
	onTabSelected:T.func,
	onBinderSelected:T.func
}

export {
	BinderNavigator
}
