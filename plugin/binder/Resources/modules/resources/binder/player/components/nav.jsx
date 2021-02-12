import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CallbackButton} from '#/main/app/buttons/callback/components/button'


import {Tab as TabType, Binder as BinderType} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/prop-types'

class BinderNavigator extends Component {
	constructor(props){
		super(props);

		// default to empty stack and displayed binder tabs
		let stack = [];
		let displayedTabs = this.props.binder.tabs;
		// fill the tab stack based on opening slug
		if(this.props.openingSlugPath.length > 0){
			this.props.openingSlugPath.split("/").forEach(
				(slug) => {
					let slugFound = !displayedTabs.every((tab) => {
						if(tab.slug === slug){
							// slug found
							stack.push(tab);
							return false;
						}
						return true;
					});
					if(slugFound && stack[stack.length - 1].metadata.type === 'binder'){
						displayedTabs = stack[stack.length - 1].content.tabs;
					}
				}
			);

		}
		
  
		this.state = {
			tabStack:stack,
			displayedTabs:displayedTabs
		}

		this.selectingBinder = this.selectingBinder.bind(this);
		this.selectingBreadcrumbTab = this.selectingBreadcrumbTab.bind(this);
		this.selectingDisplayedTab = this.selectingDisplayedTab.bind(this);
	}

	selectingBinder(){
		this.setState({
			tabStack:[],
			displayedTabs:this.props.binder.tabs
		});
		if(this.props.onBinderSelected){
		 	this.props.onBinderSelected(this.props.binder);
		}
	}

	selectingBreadcrumbTab(tab,index){

		// slice the stack based on index in the stack
		let stack = this.state.tabStack.slice(0, index+1);

		// reset displayedTabs to the last tab in the stack
		this.setState({
			tabStack:stack,
			displayedTabs:stack[index].content.tabs
		})

		if(this.props.onBinderSelected){
		 	this.props.onBinderSelected(tab.content);
		}
	}

	selectingDisplayedTab(tab,index){
		if(tab.metadata.type === 'binder'){
			let stack = this.state.tabStack.slice();
			stack.push(tab);
			this.setState({
				tabStack:stack,
				displayedTabs:tab.content.tabs
			});
		} else if(this.props.onContentSelected){
			this.props.onContentSelected(tab.content);
		}
	}

	render(){

		return (
			<Fragment>
				{this.state.tabStack.length > 0 && 
					<nav className="binder-nav" aria-label="Breadcrumb">
						<CallbackButton
								key={this.props.binder.id}
								className={classes('nav-crumb')}
								callback={()=>{this.selectingBinder()}}>					
							|<span className="visually-hidden">{trans('binder_root')}</span>
						</CallbackButton>
						{ this.state.tabStack.map(
							(tab,index) => 
								<CallbackButton
										key={tab.id}
										className={classes('nav-crumb')}
										style={{
											backgroundColor: get(tab, 'display.backgroundColor'),
											borderColor: get(tab, 'display.borderColor'),
											color: get(tab, 'display.textColor')
										}}
										callback={()=>{
											if(index !== this.state.tabStack.length - 1) {
												this.selectingBreadcrumbTab(tab,index)
											}}}>
									{tab.display.icon &&
										<span className={classes(
												'fa fa-fw', 
												`fa-${tab.display.icon}`, 
												tab.title && 'icon-with-text-right')} />
									}
									{tab.title || (tab.resourceNode && tab.resourceNode.name)}
									{index === this.state.tabStack.length - 1 && 
										<span className="visually-hidden">{trans('current_section')}</span>}
								</CallbackButton>
							)
						}
					</nav>
				}
				{this.state.displayedTabs.length > 0 && 
					<nav className="binder-nav"> {
						this.state.displayedTabs.map(
							(tab,index) => {
								if( tab.display.visible && 
										(tab.title || 
											(tab.resourceNode && tab.resourceNode.name))){
									let style = this.props.selectedSlugPath === tab.slug ?
										{
											backgroundColor: get(tab, 'display.backgroundColor'),
											borderColor: get(tab, 'display.borderColor'),
											color: get(tab, 'display.textColor')
										} : {};
									
									return (
										<CallbackButton
												key={tab.id}
												className={classes('nav-tab', {
													'nav-tab-hidden': !get(tab, 'display.visible')
												})}
												style={style}
												callback={()=>{this.selectingDisplayedTab(tab,index)}}>
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
	binder:T.shape(BinderType.propTypes),
	openingSlugPath:T.string,
	onContentSelected:T.func,
	onBinderSelected:T.func
}

export {
	BinderNavigator
}
