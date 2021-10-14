import React from 'react';
import Button from '@material-ui/core/Button';
import Box from '@material-ui/core/Box';
import LinearProgress from '@material-ui/core/LinearProgress';
import axios from 'axios';

import Table from '@material-ui/core/Table';
import TableBody from '@material-ui/core/TableBody';
import TableCell from '@material-ui/core/TableCell';
import TableContainer from '@material-ui/core/TableContainer';
import TableHead from '@material-ui/core/TableHead';
import TableRow from '@material-ui/core/TableRow';

import Paper from '@material-ui/core/Paper';

import Alert from '@material-ui/lab/Alert';

import Accordion from '@material-ui/core/Accordion';
import AccordionSummary from '@material-ui/core/AccordionSummary';
import AccordionDetails from '@material-ui/core/AccordionDetails';
import Typography from '@material-ui/core/Typography';
import ExpandMoreIcon from '@material-ui/icons/ExpandMore';
import { ThreeSixty } from '@material-ui/icons';
import { makeStyles } from '@material-ui/core/styles';

import List from '@material-ui/core/List';
import ListItem from '@material-ui/core/ListItem';
import ListItemIcon from '@material-ui/core/ListItemIcon';
import ListItemText from '@material-ui/core/ListItemText';
import Divider from '@material-ui/core/Divider';
import InboxIcon from '@material-ui/icons/Inbox';
import DraftsIcon from '@material-ui/icons/Drafts';


const useStyles = makeStyles((theme) => ({
	root: {
	  width: '100%',
	},
	heading: {
	  fontSize: theme.typography.pxToRem(15),
	  fontWeight: theme.typography.fontWeightRegular,
	},
  }));

const styles = {
	root: {
		background: "linear-gradient(45deg, green 30%, orange 90%)",
		border: 0,
		borderRadius: 3,
		boxShadow: "0 3px 5px 2px rgba(255, 105, 135, .3)",
		color: "white",
		height: 48,
		padding: "0 30px"
	  },
	  table: {
		  minWidth: 650,
	  },
	  tableHeadCell: {
		fontSize: '40pt',
	  },
	  box: {
		  marginTop: 80,
		  border: "2px solid red"
	  },
  };

  const theme = {
	background: 'linear-gradient(45deg, #FE6B8B 30%, #FF8E53 90%)',
	fontSize: '80pt',
  };

class ListImages extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: true,
			images: Array(),
		};
	}

	fetch_images() {
		const Api = axios.create({
			baseURL: smush_toolkit.data.rest_url,
			headers: {
				'content-type': 'application/json',
				'X-WP-Nonce': smush_toolkit.data.nonce
			}
		}),
		self = this,
		data = {
			action : 'fetch_unsmushed_images'
		};

		Api.post( smush_toolkit.data.rest_namespace, data ).then( function (response) {

			if ( response.data.success ) {

				if ( ! response.data.completed ) {
					self.setState({images: [...self.state.images, response.data.message ]});
				} else {
					self.setState({ loading: false });
				}

			} else {
				self.setState({ loading: false });
			}
		});
	}




	list_images( raw_backups ) {

		return (
			<TableContainer theme={styles.table} component={Paper}>
      			<Table aria-label="simple table">

        			<TableHead>
          				<TableRow>

						  <TableCell theme={theme} align="left">Image</TableCell>
						  <TableCell theme={theme} align="left">Issues</TableCell>

		  				</TableRow>
		  			</TableHead>

					<TableBody>
						{this.state.images.map((row) => (
							row.report_status == 'invalid' ?
							<TableRow>

								<TableCell align="left">{this.display_row_html( row.image_link )}</TableCell>
								<TableCell>
									{this.display_suggestions( row )}
								</TableCell>

							</TableRow>
							:
							null
						))}
					</TableBody>
		  		</Table>
		  </TableContainer>
		);
	}
	
	display_suggestions( meta ) {
		let suggestions = meta.suggestions;
		return  Object.keys(suggestions).map((item,index) => {

			return(
				<Accordion>
					<AccordionSummary
						expandIcon={<ExpandMoreIcon />}
						aria-controls="panel1a-content"
						id="panel1a-header"
						>
							<Typography><strong>{suggestions[item].title}</strong></Typography>
						</AccordionSummary>
						<AccordionDetails>
							<Typography>
								<div>{suggestions[item].issue}</div>

								<List component="nav" aria-label="main mailbox folders">

									{meta[item].length > 0 ?
									meta[item].map((meta_item)=>(
										<ListItem>
											{meta_item}
									  	</ListItem>
									))
									:
									'Empty'}
									
								</List>

								<Alert severity="info">{suggestions[item].suggestion}</Alert>
							</Typography>
						</AccordionDetails>

				</Accordion>
			)
		})

	}

	display_row_html( raw_html ) {
		return <div>
					{ <div dangerouslySetInnerHTML={{ __html: raw_html }} /> }
				</div>;
	}


	render() {
		var progressbar = '',
			notification = '';

		if ( ! ! this.state.loading ) {

			this.fetch_images();
		
			progressbar = <Box p={1} m={1}>
							<LinearProgress />
						</Box>;

		} else {
			notification = <Alert severity="success">All fetched</Alert>;
		}

		return ( 
			<div>
				<Box theme={styles.box}>
				<Box mt={2} pt={3}>{notification}</Box>
					<Box mt={2} pt={3}>{progressbar}</Box>
					{this.list_images()}
				</Box>
			</div>
			);
	}
}

export default ListImages;