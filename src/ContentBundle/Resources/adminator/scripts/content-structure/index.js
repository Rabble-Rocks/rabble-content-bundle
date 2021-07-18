import React, {Component, Fragment} from 'react';
import SortableTree from 'react-sortable-tree';
import ReactDOM from "react-dom";

class ContentStructure extends Component {
    constructor(props) {
        super(props);
        this.state = {
            treeData: this.formatTreeData(this.props.treeData),
        };
    }

    formatTreeData(data) {
        let treeData = [];

        for (let item of data) {
            if (item.children === true) {
                item.children = ({ done }) => {
                    fetch(item.get)
                        .then(res => res.json())
                        .then(
                            (result) => {
                                done(this.formatTreeData(result));
                            },
                            () => {
                                this.setState({
                                    error: true
                                });
                            }
                        );
                };
            }
            treeData.push(item)
        }
        return treeData;
    }

    generateNodeProps(data) {
        const onDelete = (event) => {
            if(!confirm('Are you sure?')) {
                event.preventDefault();
                event.stopPropagation();
            }
        };
        return {
            buttons: [
                (
                    <Fragment>
                        <div className="btn-group">
                            <a href={data.node.edit} className="btn btn-info btn-sm"><i className="fa fa-pencil" /></a>
                            <div className="dropdown">
                                <button className="btn btn-info btn-sm bdrs-0 dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                    <i className="fa fa-plus-circle" />
                                </button>
                                <ul className="dropdown-menu">
                                    {Object.keys(this.props.contentTypes).map((name) => (
                                        <li key={name}>
                                            <a className="dropdown-item"
                                               href={data.node.add[name]}>
                                                {this.props.contentTypes[name]}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <a href={data.node.delete} onClick={onDelete} className="btn btn-info btn-sm"><i className="fa fa-times" /></a>
                        </div>
                    </Fragment>
                )
            ]
        };
    }

    onMoveNode(event) {
        const itemId = event.node.id;
        const parentId = null === event.nextParentNode ? this.props.rootId : event.nextParentNode.id;
        let sortOrder = event.nextTreeIndex;
        let siblings = (null === event.nextParentNode ? event.treeData : event.nextParentNode.children)
        let i = 0;
        for (let child of siblings) {
            if (child.id === itemId) {
                sortOrder = i;
                break;
            }
            i++;
        }
        const url = this.props.moveUrl + '?item=' + itemId + '&to=' + parentId + '&sortOrder=' + sortOrder;
        fetch(url)
            .then(res => res.text())
            .then(
                (result) => {
                    console.log(result);
                },
                () => {
                    this.setState({
                        error: true
                    });
                }
            );
    }

    render() {
        return (
            <div style={{ height: 400 }}>
                <SortableTree
                    treeData={this.state.treeData}
                    onChange={treeData => this.setState({ treeData })}
                    generateNodeProps={this.generateNodeProps.bind(this)}
                    getNodeKey={({ node }) => node.id}
                    onMoveNode={this.onMoveNode.bind(this)}
                />
            </div>
        );
    }
}
let items = document.getElementsByClassName("content-structure");
for (let i = 0; i < items.length; i++) {
    let item = items.item(i);
    let tree = JSON.parse(item.dataset.tree);
    let contentTypes = JSON.parse(item.dataset.contentTypes);
    ReactDOM.render((
        <ContentStructure treeData={tree} contentTypes={contentTypes} moveUrl={item.dataset.moveUrl} rootId={item.dataset.rootId} />), item);
}