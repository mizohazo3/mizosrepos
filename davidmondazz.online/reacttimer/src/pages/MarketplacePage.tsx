import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  Box,
  Container,
  Heading,
  SimpleGrid,
  Card,
  CardHeader,
  CardBody,
  CardFooter,
  Button,
  Text,
  Image,
  useToast,
  useColorModeValue,
  Flex,
  Badge,
  Input,
  FormControl,
  FormLabel,
  Textarea,
  Divider,
  HStack,
  VStack,
  useDisclosure,
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalFooter,
  ModalBody,
  ModalCloseButton,
  Icon,
  Tooltip,
  InputGroup,
  InputLeftElement,
  ScaleFade,
  Spinner,
  Center,
  Grid,
  GridItem,
  IconButton,
  InputRightElement,
  AspectRatio,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs
} from '@chakra-ui/react';
import { FaPlus, FaCoins, FaShoppingCart, FaCheck, FaLock, FaTimes, FaUpload, FaSearch, FaImage } from 'react-icons/fa';
import { TimerService } from '../services/firebase';
import { MarketplaceItem, PurchaseHistory } from '../types';
import { keyframes } from '@emotion/react';
import { searchImages, SearchResult } from '../services/imageSearch';
import RecentPurchases from '../components/RecentPurchases';

const shimmer = keyframes`
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
`;

const fadeIn = keyframes`
  0% { opacity: 0; transform: translateY(10px); }
  100% { opacity: 1; transform: translateY(0); }
`;

const timerService = new TimerService();

// Image resizing utility
const resizeImage = (file: File, maxWidth = 800, maxHeight = 600): Promise<string> => {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (event) => {
      const img = document.createElement('img') as HTMLImageElement;
      img.src = event.target?.result as string;
      img.onload = () => {
        const canvas = document.createElement('canvas');
        let width = img.width;
        let height = img.height;
        
        // Calculate new dimensions while maintaining aspect ratio
        if (width > height) {
          if (width > maxWidth) {
            height = Math.round(height * (maxWidth / width));
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width = Math.round(width * (maxHeight / height));
            height = maxHeight;
          }
        }
        
        canvas.width = width;
        canvas.height = height;
        
        const ctx = canvas.getContext('2d');
        ctx?.drawImage(img, 0, 0, width, height);
        
        const resizedImage = canvas.toDataURL('image/jpeg', 0.85);
        resolve(resizedImage);
      };
    };
  });
};

const MarketplacePage: React.FC = () => {
  const [items, setItems] = useState<MarketplaceItem[]>([]);
  const [purchases, setPurchases] = useState<PurchaseHistory[]>([]);
  const [bankBalance, setBankBalance] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [newItem, setNewItem] = useState<{
    name: string;
    price: number;
    description: string;
    imageUrl: string;
  }>({
    name: '',
    price: 0,
    description: '',
    imageUrl: ''
  });
  const [selectedItem, setSelectedItem] = useState<MarketplaceItem | null>(null);
  const { isOpen, onOpen, onClose } = useDisclosure();
  const { isOpen: isConfirmOpen, onOpen: onConfirmOpen, onClose: onConfirmClose } = useDisclosure();
  const { isOpen: isDeleteOpen, onOpen: onDeleteOpen, onClose: onDeleteClose } = useDisclosure();
  const [itemToDelete, setItemToDelete] = useState<MarketplaceItem | null>(null);
  const toast = useToast();

  // Color mode values
  const cardBg = useColorModeValue('white', 'gray.800');
  const cardHoverBg = useColorModeValue('gray.50', 'gray.700');
  const cardBorder = useColorModeValue('gray.200', 'gray.700');
  const cardActiveBorder = useColorModeValue('green.300', 'green.500');
  const headingColor = useColorModeValue('gray.700', 'white');
  const textColor = useColorModeValue('gray.600', 'gray.300');
  const priceColor = useColorModeValue('green.600', 'green.300');
  const formBg = useColorModeValue('gray.50', 'gray.900');
  const ownedBadgeBg = useColorModeValue('green.100', 'green.900');
  const ownedBadgeColor = useColorModeValue('green.800', 'green.200');
  const disabledButtonBg = useColorModeValue('gray.100', 'gray.700');
  const disabledButtonColor = useColorModeValue('gray.500', 'gray.500');

  // New state variables for image handling
  const [uploadedImage, setUploadedImage] = useState<string>('');
  const [isSearching, setIsSearching] = useState<boolean>(false);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [activeTab, setActiveTab] = useState<number>(0);

  // Fetch marketplace items, purchases, and bank balance
  useEffect(() => {
    const fetchData = async () => {
      setIsLoading(true);
      try {
        // Subscribe to marketplace items
        const unsubscribeItems = timerService.subscribeToMarketplaceItems((marketplaceItems) => {
          setItems(marketplaceItems);
        });

        // Subscribe to purchases
        const unsubscribePurchases = timerService.subscribeToPurchaseHistory((purchaseHistory) => {
          setPurchases(purchaseHistory);
        });

        // Subscribe to bank balance
        const unsubscribeSessions = timerService.subscribeToSessions((sessions) => {
          const totalEarnings = sessions.reduce((sum, session) => sum + (session.earnings || 0), 0);
          setBankBalance(totalEarnings);
        });

        return () => {
          unsubscribeItems();
          unsubscribePurchases();
          unsubscribeSessions();
        };
      } catch (error) {
        console.error('Error fetching marketplace data:', error);
        toast({
          title: 'Error',
          description: 'Could not load marketplace data',
          status: 'error',
          duration: 5000,
          isClosable: true,
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [toast]);

  // Check if user owns an item
  const isItemOwned = (itemId: string): boolean => {
    // Always return false so items can be purchased multiple times
    return false;
  };

  // Check if user can afford an item
  const canAffordItem = (price: number): boolean => {
    return bankBalance >= price;
  };

  // Handle image upload
  const handleImageUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    try {
      const file = event.target.files?.[0];
      if (!file) return;

      if (!file.type.startsWith('image/')) {
        toast({
          title: 'Invalid file',
          description: 'Please upload an image file',
          status: 'error',
          duration: 3000,
          isClosable: true,
        });
        return;
      }

      const resizedImage = await resizeImage(file);
      setUploadedImage(resizedImage);
      setNewItem({...newItem, imageUrl: resizedImage});
      
      // Reset the file input
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (error) {
      console.error('Error uploading image:', error);
      toast({
        title: 'Upload Error',
        description: 'Could not process the image',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  // Handle image search
  const handleImageSearch = useCallback(async () => {
    if (!searchQuery.trim()) return;
    
    try {
      setIsSearching(true);
      const results = await searchImages(searchQuery);
      setSearchResults(results);
    } catch (error) {
      console.error('Error searching for images:', error);
      toast({
        title: 'Search Error',
        description: 'Could not search for images',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsSearching(false);
    }
  }, [searchQuery, toast]);

  // Handle selecting image from search results
  const handleSelectImage = useCallback(async (result: SearchResult) => {
    try {
      // Fetch the image and resize it
      const response = await fetch(result.url);
      const blob = await response.blob();
      const resizedImage = await resizeImage(blob as File);
      
      setUploadedImage(resizedImage);
      setNewItem({...newItem, imageUrl: resizedImage});
      setActiveTab(0); // Switch back to upload tab
      
      // Show attribution toast
      toast({
        title: 'Photo Credits',
        description: `Photo by ${result.authorName} on Unsplash`,
        status: 'info',
        duration: 5000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error selecting image:', error);
      toast({
        title: 'Error',
        description: 'Could not process the selected image',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  }, [newItem, toast]);

  // Modified handleCreateItem to use new image upload functionality
  const handleCreateItem = async () => {
    try {
      if (!newItem.name || newItem.price <= 0) {
        toast({
          title: 'Validation Error',
          description: 'Please provide a name and a valid price.',
          status: 'error',
          duration: 3000,
          isClosable: true,
        });
        return;
      }

      const itemData = {
        name: newItem.name,
        price: newItem.price,
        description: newItem.description,
        imageUrl: ''
      };

      // Use the image upload method if we have an uploaded image
      if (uploadedImage) {
        await timerService.createMarketplaceItemWithImage(itemData, uploadedImage);
      } else {
        await timerService.createMarketplaceItem(itemData);
      }

      // Reset form
      setNewItem({
        name: '',
        price: 0,
        description: '',
        imageUrl: ''
      });
      setUploadedImage('');
      setSearchResults([]);
      setSearchQuery('');

      onClose();

      toast({
        title: 'Item Created',
        description: `${newItem.name} has been added to the marketplace.`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error creating item:', error);
      toast({
        title: 'Error',
        description: 'Could not create item. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    }
  };

  // Handle item purchase
  const handlePurchaseItem = async (item: MarketplaceItem) => {
    try {
      await timerService.purchaseItem(item.id, item.name, item.price);

      toast({
        title: 'Purchase Successful',
        description: `You've purchased ${item.name} for $${item.price.toFixed(2)}.`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });

      onConfirmClose();
    } catch (error) {
      console.error('Error purchasing item:', error);
      toast({
        title: 'Purchase Failed',
        description: error instanceof Error ? error.message : 'Could not complete purchase.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    }
  };

  // Modal for confirming purchase
  const handleConfirmPurchase = (item: MarketplaceItem) => {
    setSelectedItem(item);
    onConfirmOpen();
  };

  // Handle item deletion
  const handleDeleteItem = async (item: MarketplaceItem) => {
    setItemToDelete(item);
    onDeleteOpen();
  };

  // Confirm item deletion
  const confirmDeleteItem = async () => {
    try {
      if (!itemToDelete) return;

      await timerService.deleteMarketplaceItemWithImage(itemToDelete.id);
      
      onDeleteClose();
      
      toast({
        title: 'Item Deleted',
        description: `${itemToDelete.name} has been removed from the marketplace.`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error deleting item:', error);
      toast({
        title: 'Delete Failed',
        description: 'Could not delete item. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    }
  };

  if (isLoading) {
    return (
      <Center minH="300px">
        <VStack spacing={4}>
          <Spinner size="xl" color="blue.500" thickness="4px" speed="0.65s" />
          <Text color={textColor}>Loading marketplace...</Text>
        </VStack>
      </Center>
    );
  }

  return (
    <Container maxW="container.xl" py={8}>
      <Box mb={8}>
        <Flex justifyContent="space-between" alignItems="center" mb={6}>
          <Heading size="xl" color={headingColor}>Marketplace</Heading>
          <Button 
            leftIcon={<FaPlus />} 
            colorScheme="blue" 
            onClick={onOpen}
          >
            Add Item
          </Button>
        </Flex>
        
        <Divider mb={8} />

        {items.length === 0 ? (
          <Center p={10} borderWidth="1px" borderRadius="xl" borderStyle="dashed">
            <VStack spacing={4}>
              <Icon as={FaShoppingCart} boxSize={12} color="gray.400" />
              <Text color={textColor} textAlign="center">
                No items in the marketplace yet. Add your first item!
              </Text>
              <Button 
                leftIcon={<FaPlus />} 
                colorScheme="blue" 
                onClick={onOpen}
              >
                Add Item
              </Button>
            </VStack>
          </Center>
        ) : (
          <SimpleGrid columns={{ base: 1, md: 3, lg: 4 }} spacing={4}>
            {items.map((item) => {
              const owned = false; // Always set to false to make items always available
              const canAfford = canAffordItem(item.price);
              const isCurrentUserItem = true;
              
              return (
                <ScaleFade key={item.id} in={true} initialScale={0.9}>
                  <Card 
                    borderWidth="1px" 
                    borderRadius="lg" 
                    overflow="hidden"
                    borderColor={cardBorder}
                    bg={cardBg}
                    boxShadow="md"
                    position="relative"
                    transition="all 0.3s"
                    _hover={{
                      transform: "translateY(-4px)",
                      boxShadow: "lg",
                      borderColor: "blue.300"
                    }}
                  >
                    {/* Remove the owned badge */}
                    
                    {/* Add delete button for items the user created */}
                    {isCurrentUserItem && (
                      <IconButton
                        aria-label="Delete item"
                        icon={<FaTimes />}
                        size="sm"
                        colorScheme="red"
                        variant="ghost"
                        position="absolute"
                        top={2}
                        right={2}
                        zIndex={1}
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDeleteItem(item);
                        }}
                      />
                    )}
                    
                    {item.imageUrl ? (
                      <Image 
                        src={item.imageUrl} 
                        alt={item.name} 
                        height="140px" 
                        width="100%" 
                        objectFit="cover"
                        cursor={canAfford ? "pointer" : "default"}
                        onClick={() => canAfford && handleConfirmPurchase(item)}
                        _hover={canAfford ? {
                          opacity: 0.9,
                          transform: "scale(1.02)",
                          transition: "all 0.2s"
                        } : {}}
                        fallback={
                          <Center height="140px" bg="gray.100" _dark={{ bg: "gray.700" }}>
                            <Text color="gray.500">No Image</Text>
                          </Center>
                        }
                      />
                    ) : (
                      <Center 
                        height="140px" 
                        bg="gray.100" 
                        _dark={{ bg: "gray.700" }}
                        cursor={canAfford ? "pointer" : "default"}
                        onClick={() => canAfford && handleConfirmPurchase(item)}
                        _hover={canAfford ? {
                          opacity: 0.9,
                          bg: "gray.200",
                          _dark: { bg: "gray.600" },
                          transition: "all 0.2s"
                        } : {}}
                      >
                        <Icon as={FaShoppingCart} boxSize={8} color="gray.400" />
                      </Center>
                    )}
                    
                    <CardHeader pb={2} pt={2}>
                      <Heading size="md" color={headingColor} noOfLines={1}>{item.name}</Heading>
                    </CardHeader>
                    
                    <CardFooter pt={0} pb={2} justifyContent="space-between" alignItems="center">
                      <Text color={priceColor} fontWeight="bold" fontSize="lg">
                        ${item.price.toFixed(2)}
                      </Text>
                      
                      {/* Always show the Buy Now button */}
                      <Tooltip 
                        label={!canAfford ? "Insufficient funds" : "Purchase this item"} 
                        placement="top"
                        hasArrow
                      >
                        <Button
                          rightIcon={canAfford ? <FaShoppingCart /> : <FaLock />}
                          colorScheme={canAfford ? "blue" : "gray"}
                          isDisabled={!canAfford}
                          onClick={() => handleConfirmPurchase(item)}
                          opacity={canAfford ? 1 : 0.7}
                          _disabled={{
                            bg: disabledButtonBg,
                            color: disabledButtonColor,
                            cursor: "not-allowed"
                          }}
                          size="sm"
                        >
                          Buy Now
                        </Button>
                      </Tooltip>
                    </CardFooter>
                  </Card>
                </ScaleFade>
              );
            })}
          </SimpleGrid>
        )}
      </Box>

      {/* Recent Purchases Section */}
      <Box mt={12} mb={8}>
        <Divider mb={8} />
        <RecentPurchases limit={5} />
      </Box>

      {/* Add Item Modal - Updated with image upload and search */}
      <Modal isOpen={isOpen} onClose={onClose} size="xl">
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>Add New Item</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <VStack spacing={4}>
              <FormControl isRequired>
                <FormLabel>Item Name</FormLabel>
                <Input 
                  placeholder="Enter item name"
                  value={newItem.name}
                  onChange={(e) => setNewItem({...newItem, name: e.target.value})}
                />
              </FormControl>
              
              <FormControl isRequired>
                <FormLabel>Price</FormLabel>
                <InputGroup>
                  <InputLeftElement pointerEvents="none">
                    <Icon as={FaCoins} color="gray.500" />
                  </InputLeftElement>
                  <Input 
                    type="number" 
                    placeholder="0.00"
                    value={newItem.price === 0 ? '' : newItem.price}
                    onChange={(e) => setNewItem({...newItem, price: parseFloat(e.target.value) || 0})}
                  />
                </InputGroup>
              </FormControl>
              
              <FormControl>
                <FormLabel>Description</FormLabel>
                <Textarea 
                  placeholder="Describe your item"
                  value={newItem.description}
                  onChange={(e) => setNewItem({...newItem, description: e.target.value})}
                />
              </FormControl>
              
              <FormControl>
                <FormLabel>Image</FormLabel>
                <Tabs isFitted variant="enclosed" index={activeTab} onChange={setActiveTab}>
                  <TabList mb="1em">
                    <Tab>Upload Image</Tab>
                    <Tab>Search Images</Tab>
                  </TabList>
                  <TabPanels>
                    <TabPanel p={0}>
                      <VStack spacing={4}>
                        {uploadedImage ? (
                          <Box position="relative" w="100%">
                            <Image 
                              src={uploadedImage} 
                              alt="Uploaded preview"
                              borderRadius="md"
                              maxH="200px"
                              mx="auto"
                            />
                            <IconButton
                              aria-label="Remove image"
                              icon={<FaTimes />}
                              size="sm"
                              colorScheme="red"
                              position="absolute"
                              top={2}
                              right={2}
                              onClick={() => {
                                setUploadedImage('');
                                setNewItem({...newItem, imageUrl: ''});
                              }}
                            />
                          </Box>
                        ) : (
                          <Center 
                            p={6} 
                            borderWidth="2px" 
                            borderRadius="md" 
                            borderStyle="dashed"
                            borderColor="gray.300"
                            bg="gray.50"
                            _dark={{ borderColor: "gray.600", bg: "gray.800" }}
                            w="100%"
                          >
                            <VStack spacing={2}>
                              <Icon as={FaImage} boxSize={10} color="gray.400" />
                              <Text color="gray.500">No image uploaded</Text>
                              <Button
                                leftIcon={<FaUpload />}
                                onClick={() => fileInputRef.current?.click()}
                                size="sm"
                                colorScheme="blue"
                              >
                                Choose Image
                              </Button>
                              <Input
                                type="file"
                                accept="image/*"
                                ref={fileInputRef}
                                onChange={handleImageUpload}
                                display="none"
                              />
                            </VStack>
                          </Center>
                        )}
                        
                        {!uploadedImage && (
                          <Text fontSize="sm" color="gray.500">
                            Upload an image for your item. Images will be resized automatically.
                          </Text>
                        )}
                      </VStack>
                    </TabPanel>
                    
                    <TabPanel p={0}>
                      <VStack spacing={4}>
                        <InputGroup>
                          <Input
                            placeholder="Search for free images..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            onKeyPress={(e) => e.key === 'Enter' && handleImageSearch()}
                          />
                          <InputRightElement>
                            <IconButton
                              aria-label="Search images"
                              icon={<FaSearch />}
                              size="sm"
                              onClick={handleImageSearch}
                              isLoading={isSearching}
                            />
                          </InputRightElement>
                        </InputGroup>
                        
                        {isSearching ? (
                          <Center p={10}>
                            <Spinner />
                          </Center>
                        ) : searchResults.length > 0 ? (
                          <SimpleGrid columns={3} spacing={2} w="100%">
                            {searchResults.map((result) => (
                              <Box key={result.id} cursor="pointer" onClick={() => handleSelectImage(result)}>
                                <AspectRatio ratio={4/3}>
                                  <Image
                                    src={result.thumb}
                                    alt={result.description}
                                    objectFit="cover"
                                    borderRadius="md"
                                    _hover={{ 
                                      transform: "scale(1.05)", 
                                      boxShadow: "md",
                                      transition: "all 0.2s" 
                                    }}
                                    fallback={
                                      <Center bg="gray.100" _dark={{ bg: "gray.700" }}>
                                        <Spinner size="sm" />
                                      </Center>
                                    }
                                  />
                                </AspectRatio>
                                <Text fontSize="xs" color="gray.500" mt={1} noOfLines={1}>
                                  Photo by {result.authorName}
                                </Text>
                              </Box>
                            ))}
                          </SimpleGrid>
                        ) : (
                          <Center 
                            p={6} 
                            borderWidth="2px" 
                            borderRadius="md" 
                            borderStyle="dashed"
                            borderColor="gray.300"
                            bg="gray.50"
                            _dark={{ borderColor: "gray.600", bg: "gray.800" }}
                            w="100%"
                          >
                            <VStack spacing={2}>
                              <Icon as={FaSearch} boxSize={10} color="gray.400" />
                              <Text color="gray.500">
                                {searchQuery ? 'No results found' : 'Search for images'}
                              </Text>
                            </VStack>
                          </Center>
                        )}
                        
                        <Text fontSize="sm" color="gray.500">
                          Search for free images to use for your item. Images will be resized automatically.
                        </Text>
                      </VStack>
                    </TabPanel>
                  </TabPanels>
                </Tabs>
              </FormControl>
            </VStack>
          </ModalBody>

          <ModalFooter>
            <Button variant="ghost" mr={3} onClick={onClose}>
              Cancel
            </Button>
            <Button colorScheme="blue" onClick={handleCreateItem}>
              Create Item
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      {/* Purchase Confirmation Modal */}
      <Modal isOpen={isConfirmOpen} onClose={onConfirmClose}>
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>Confirm Purchase</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            {selectedItem && (
              <VStack align="stretch" spacing={4}>
                <Text>Are you sure you want to purchase <strong>{selectedItem.name}</strong>?</Text>
                <Text fontSize="sm" color="gray.500">
                  Note: You can purchase this item multiple times.
                </Text>
                <HStack justifyContent="space-between">
                  <Text>Price:</Text>
                  <Text fontWeight="bold" color={priceColor}>${selectedItem.price.toFixed(2)}</Text>
                </HStack>
                <HStack justifyContent="space-between">
                  <Text>Your Balance:</Text>
                  <Text fontWeight="bold">${bankBalance.toFixed(2)}</Text>
                </HStack>
                <Divider />
                <HStack justifyContent="space-between">
                  <Text>Balance After Purchase:</Text>
                  <Text fontWeight="bold" color={bankBalance - selectedItem.price < 0 ? 'red.500' : 'green.500'}>
                    ${(bankBalance - selectedItem.price).toFixed(2)}
                  </Text>
                </HStack>
              </VStack>
            )}
          </ModalBody>

          <ModalFooter>
            <Button variant="outline" colorScheme="red" mr={3} leftIcon={<FaTimes />} onClick={onConfirmClose}>
              Cancel
            </Button>
            <Button 
              colorScheme="green" 
              leftIcon={<FaCheck />}
              onClick={() => selectedItem && handlePurchaseItem(selectedItem)}
            >
              Confirm Purchase
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal isOpen={isDeleteOpen} onClose={onDeleteClose}>
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>Confirm Deletion</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            {itemToDelete && (
              <VStack align="stretch" spacing={4}>
                <Text>
                  Are you sure you want to delete <strong>{itemToDelete.name}</strong>?
                </Text>
                <Text color="red.500" fontWeight="bold">
                  This action cannot be undone.
                </Text>
                {itemToDelete.imageUrl && (
                  <Box>
                    <Image 
                      src={itemToDelete.imageUrl} 
                      alt={itemToDelete.name}
                      maxH="150px"
                      mx="auto"
                      borderRadius="md"
                    />
                  </Box>
                )}
              </VStack>
            )}
          </ModalBody>

          <ModalFooter>
            <Button variant="outline" mr={3} onClick={onDeleteClose}>
              Cancel
            </Button>
            <Button colorScheme="red" onClick={confirmDeleteItem}>
              Delete Item
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </Container>
  );
};

export default MarketplacePage; 