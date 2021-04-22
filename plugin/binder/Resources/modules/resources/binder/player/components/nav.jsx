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
		// first check if the dis
		if(this.props.selectedSlug && this.props.selectedSlug.length > 0){
			this.props.selectedSlug.split("/").forEach(
				(searchedSlugPart) => {
					let slugFound = !displayedTabs.every((tab) => {
						const tabNodeSlugs = tab.slug.split("/");
						const tabSlugPart = tabNodeSlugs[tabNodeSlugs.length - 1];
						// Check if the last part of the tab slug match the search slugpart
						if(tabSlugPart === searchedSlugPart){
							// slug found
							stack.push(tab);
							return false;
						}
						return true;
					});
					if(slugFound){
						if(stack[stack.length - 1].metadata.type === 'binder'){
							displayedTabs = stack[stack.length - 1].content.binder.tabs;
						} else { // unpop the document tab from the breadcrumb stack
							stack.pop();
						}
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
		console.log(stack);
		this.setState({
			tabStack:stack,
			displayedTabs:stack[index].content.binder.tabs
		})

		if(this.props.onBinderSelected){
		 	this.props.onBinderSelected(tab.content.binder, tab.slug);
		}
	}

	selectingDisplayedTab(tab,index){
		if(tab.metadata.type === 'binder'){
			let stack = this.state.tabStack.slice();
			stack.push(tab);

			this.setState({
				tabStack:stack,
				displayedTabs:tab.content.binder.tabs
			});
		} else if(this.props.onContentSelected){
			this.props.onContentSelected(tab.content, tab.slug);
		}
	}

	render(){
		const tabsCrumb = this.state.tabStack.map(
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
								if(index !== tabsCrumb.length - 1) {
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
		)
		
		return (
			<Fragment>
				{tabsCrumb.length > 0 &&
					<nav className="binder-nav" aria-label="Breadcrumb">
						<CallbackButton
								key={this.props.binder.id}
								className={classes('nav-crumb')}
								callback={()=>{this.selectingBinder()}}>					
							|<span className="visually-hidden">{trans('binder_root')}</span>
						</CallbackButton>
						{tabsCrumb}
					</nav>
				}
				{this.state.displayedTabs.length > 0 && 
					<nav className="binder-nav"> {
						this.state.displayedTabs.map(
							(tab,index) => {
								if( tab.display.visible && 
										(tab.title || 
											(tab.resourceNode && tab.resourceNode.name))){
									let style = this.props.selectedSlug === tab.slug ?
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
													'active': this.props.selectedSlug === tab.slug
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
	selectedSlug:T.string,
	onContentSelected:T.func,
	onBinderSelected:T.func
}

export {
	BinderNavigator
}
