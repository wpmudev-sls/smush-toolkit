import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import Paper from '@material-ui/core/Paper';
import Grid from '@material-ui/core/Grid';
import Box from '@material-ui/core/Box';
import ListImages from './listimages.js';

class SmushToolkit extends React.Component {
	
	render() {

		const classes = makeStyles((theme) => ({
			root: {
				flexGrow: 2,
				marginAutoItem: {
					margin: 'auto'
				}
			},
			paper: {
				padding: theme.spacing(5),
				textAlign: 'center',
				color: theme.palette.text.secondary,
			}
		}));

		return (
			<div className={classes.root}>
				<h1>{smush_toolkit.labels.page_title}</h1>
				<Grid container spacing={3}>
					<Grid item xs={11}>
						<Paper className={classes.paper}>
							<Box p={1} m={1}>
								<ListImages init={true}></ListImages>
							</Box>
						</Paper>
					</Grid>

				</Grid>

			</div>
		);
	}
}

ReactDOM.render(
	<SmushToolkit />,
	document.getElementById(smush_toolkit.data.unique_id)
);
